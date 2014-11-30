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
        'delay' => null,
        'database' => 'database_name',
        'expires_in' => null,
        'user' => null,
        'pass' => null,
        'persistent' => true,
        'port' => 3306,
        'priority' => 0,
        'queue' => 'default',
        'host' => '127.0.0.1',
        'table' => 'jobs',
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

        try {
            $this->connection = new PDO(
                $dsn,
                $config['user'],
                $config['pass'],
                $flags
            );
        } catch (PDOException $e) {
            // TODO: Logging
            $this->connection = false;
        }

        return (bool)$this->connection;
    }

    /**
     * {@inheritDoc}
     */
    public function delete($item)
    {
        if (!parent::delete($item)) {
            return false;
        }

        $sql = sprintf('DELETE FROM `%s` WHERE id = ?', $this->config('table'));
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
        $selectSql = sprintf($selectSql, $this->config('table'));
        $updateSql = sprintf('UPDATE `%s` SET locked = 1 WHERE id = ?', $this->config('table'));

        $datetime = new DateTime;
        $dtFormatted = $datetime->format('Y-m-d H:i:s');

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
                        'queue' => $queue,
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
        $expiresIn = $this->setting($options, 'expires_in');
        $queue = $this->setting($options, 'queue');
        $priority = $this->setting($options, 'priority');

        $delayUntil = null;
        if ($delay !== null) {
            $datetime = new DateTime;
            $delayUntil = $datetime->add(new DateInterval(sprintf('PT%sS', $delay)))->format('Y-m-d H:i:s');
        }

        $expiresAt = null;
        if ($expiresIn !== null) {
            $datetime = new DateTime;
            $expiresAt = $datetime->add(new DateInterval(sprintf('PT%sS', $expiresIn)))->format('Y-m-d H:i:s');
        }

        $data = json_encode(compact('class', 'vars'));

        $sql = 'INSERT INTO `%s` (`data`, `queue`, `priority`, `expires_at`, `delay_until`) VALUES (?, ?, ?, ?, ?)';
        $sql = sprintf($sql, $this->config('table'));
        $sth = $this->connection()->prepare($sql);
        $sth->bindParam(1, $data, PDO::PARAM_STR);
        $sth->bindParam(2, $queue, PDO::PARAM_STR);
        $sth->bindParam(3, $priority, PDO::PARAM_INT);
        $sth->bindParam(4, $expiresAt, PDO::PARAM_STR);
        $sth->bindParam(5, $delayUntil, PDO::PARAM_STR);
        $sth->execute();

        if ($sth->rowCount() == 1) {
            $this->lastJobId = $this->connection()->lastInsertId();
        }
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
        $sql = sprintf($sql, $this->config('table'));
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
        $sql = sprintf('UPDATE `%s` SET locked = 0 WHERE id = ? LIMIT 1', $this->config('table'));
        $sth = $this->connection()->prepare($sql);
        $sth->bindParam(1, $item['id'], PDO::PARAM_INT);
        $sth->execute();
        return $sth->rowCount() == 1;
    }
}
