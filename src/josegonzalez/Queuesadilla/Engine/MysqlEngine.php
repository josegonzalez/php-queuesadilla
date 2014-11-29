<?php

/*
CREATE TABLE IF NOT EXISTS `jobs` (
  `id` mediumint(20) NOT NULL AUTO_INCREMENT,
  `queue` char(32) NOT NULL DEFAULT 'default',
  `data` mediumtext NOT NULL,
  `priority` int(1) NOT NULL DEFAULT '0',
  `expires_at` datetime DEFAULT NULL,
  `delay_until` datetime DEFAULT NULL,
  `locked` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `queue` (`queue`,`locked`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
*/

namespace josegonzalez\Queuesadilla\Engine;

use DateInterval;
use DateTime;
use PDO;
use PDOException;
use josegonzalez\Queuesadilla\Engine\Base;

class MysqlEngine extends Base
{
    protected $baseConfig = [
        'api_version' => 1,  # unsupported
        'delay' => null,
        'database' => 'database_name',
        'expires_in' => null,
        'user' => 'root',
        'pass' => 'pass',
        'persistent' => true,
        'port' => '3306',
        'priority' => 0,
        'protocol' => 'https',  # unsupported
        'queue' => 'default',
        'host' => '127.0.0.1',
        'table' => 'jobs',
        'time_to_run' => 60,  # unsupported
        'timeout' => 0,  # unsupported
    ];


    /**
     * {@inheritDoc}
     */
    public function connect()
    {
        $config = $this->settings;
        if (empty($config['flags'])) {
            $config['flags'] = [];
        }

        $flags = [
            PDO::ATTR_PERSISTENT => $config['persistent'],
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ] + $config['flags'];

        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']}";

        $this->connection = new PDO(
            $dsn,
            $config['user'],
            $config['pass'],
            $flags
        );

        return (bool)$this->connection;
    }

    /**
     * {@inheritDoc}
     */
    public function delete($item)
    {
        if (!is_array($item) || !isset($item['id'])) {
            return false;
        }

        $sql = sprintf('DELETE FROM `%s` WHERE id = ?', $this->settings['table']);
        $sth = $this->connection()->prepare($sql);
        $sth->bindParam(1, $item['id'], PDO::PARAM_INT);
        $sth->execute();
        return $sth->rowCount() == 1;
    }

    /**
     * {@inheritDoc}
     */
    public function pop($options = [])
    {
        $queue = $this->setting($options, 'queue');
        $selectSql = implode(" ", [
            'SELECT `id`, `data` FROM `%s`',
            'WHERE `queue` = ? AND `locked` != 1',
            'AND (expires_at IS NULL OR expires_at > ?)',
            'AND (delay_until IS NULL OR delay_until < ?)',
            'ORDER BY priority ASC LIMIT 1 FOR UPDATE',
        ]);
        $selectSql = sprintf($selectSql, $this->settings['table']);
        $updateSql = sprintf('UPDATE `%s` SET locked = 1 WHERE id = ?', $this->settings['table']);

        $dt = new DateTime;
        $dtFormatted = $dt->format('Y-m-d H:i:s');

        try {
            $sth = $this->connection()->prepare($selectSql);
            $sth->bindParam(1, $queue, PDO::PARAM_STR);
            $sth->bindParam(2, $dtFormatted, PDO::PARAM_STR);
            $sth->bindParam(3, $dtFormatted, PDO::PARAM_STR);

            $this->connection()->beginTransaction();
            $sth->execute();
            $result = $sth->fetch(PDO::FETCH_ASSOC);

            if (!empty($result)) {
                $sth = $this->connection()->prepare($updateSql);
                $sth->bindParam(1, $result['id'], PDO::PARAM_INT);
                $sth->execute();
                $this->connection()->commit();
                if ($sth->rowCount() == 1) {
                    $data = json_decode($result['data'], true);
                    return [
                        'id' => $result['id'],
                        'class' => $data['class'],
                        'vars' => $data['vars'],
                    ];
                }
            }
            $this->connection()->commit();
        } catch (PDOException $e) {
            $this->connection()->rollBack();
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function push($class, $vars = [], $options = [])
    {
        $delay = $this->setting($options, 'delay');
        $expires_in = $this->setting($options, 'expires_in');
        $queue = $this->setting($options, 'queue');
        $priority = $this->setting($options, 'priority');

        $delay_until = null;
        if ($delay !== null) {
            $dt = new DateTime;
            $delay_until = $dt->add(new DateInterval(sprintf('PT%sS', $delay)))->format('Y-m-d H:i:s');
        }

        $expires_at = null;
        if ($expires_in !== null) {
            $dt = new DateTime;
            $expires_at = $dt->add(new DateInterval(sprintf('PT%sS', $expires_in)))->format('Y-m-d H:i:s');
        }

        $data = json_encode(compact('class', 'vars'));

        $sql = 'INSERT INTO `%s` (`data`, `queue`, `priority`, `expires_at`, `delay_until`) VALUES (?, ?, ?, ?, ?)';
        $sql = sprintf($sql, $this->settings['table']);
        $sth = $this->connection()->prepare($sql);
        $sth->bindParam(1, $data, PDO::PARAM_STR);
        $sth->bindParam(2, $queue, PDO::PARAM_STR);
        $sth->bindParam(3, $priority, PDO::PARAM_INT);
        $sth->bindParam(4, $expires_at, PDO::PARAM_STR);
        $sth->bindParam(5, $delay_until, PDO::PARAM_STR);
        $sth->execute();
        return $sth->rowCount() == 1;
    }

    /**
     * {@inheritDoc}
     */
    public function queues()
    {
        $sql = implode(" ", [
            'SELECT `queue` FROM `%s`',
            'GROUP BY `queue`',
        ]);
        $sql = sprintf($sql, $this->settings['table']);
        $sth = $this->connection()->prepare($sql);
        $sth->execute();
        $results = $sth->fetchAll(PDO::FETCH_ASSOC);

        if (empty($results)) {
            return [];
        }
        return array_map(function ($result) {
            return $result['queue'];
        }, $results);
    }

    /**
     * {@inheritDoc}
     */
    public function release($item, $options = [])
    {
        $queue = $this->setting($options, 'queue');
        $sql = sprintf('UPDATE `%s` SET locked = 0 WHERE id = ?', $this->settings['table']);
        $sth = $this->connection()->prepare($sql);
        $sth->bindParam(1, $item['id'], PDO::PARAM_INT);
        $sth->execute();
        return $sth->rowCount() == 1;
    }

/**
 * Executes given SQL statement.
 *
 * @param string $sql SQL statement
 * @param array $params list of params to be bound to query
 * @param array $prepareOptions Options to be used in the prepare statement
 * @return mixed PDOStatement if query executes with no problem, true as the result of a successful, false on error
 * query returning no rows, such as a CREATE statement, false otherwise
 * @throws PDOException
 */
    protected function execute($sql, $params = [], $prepareOptions = [])
    {
        $sql = trim($sql);
        try {
            $query = $this->connection()->prepare($sql, $prepareOptions);
            $query->setFetchMode(PDO::FETCH_LAZY);
            if (!$query->execute($params)) {
                $query->closeCursor();
                return false;
            }
            if (!$query->columnCount()) {
                $query->closeCursor();
                if (!$query->rowCount()) {
                    return true;
                }
            }
            return $query;
        } catch (PDOException $e) {
            if (isset($query->queryString)) {
                $e->queryString = $query->queryString;
            } else {
                $e->queryString = $sql;
            }
            throw $e;
        }
    }
}
