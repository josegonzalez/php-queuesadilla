<?php

/*
CREATE TABLE IF NOT EXISTS `jobs` (
    `id` mediumint(20) NOT NULL AUTO_INCREMENT,
    `queue` char(32) NULL DEFAULT 'default',
    `data` mediumtext NULL DEFAULT '',
    `locked` tinyint(1) NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `queue` (`queue`, `locked`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
*/

namespace josegonzalez\Queuesadilla\Backend;

use \PDO;
use \PDOException;
use \josegonzalez\Queuesadilla\Backend;

class MysqlBackend extends Backend
{
    protected $baseConfig = array(
        'database' => 'queuesadilla',
        'login' => 'root',
        'password' => 'password',
        'persistent' => true,
        'port' => '3306',
        'queue' => 'default',
        'server' => '127.0.0.1',
        'table' => 'jobs',
    );

    public function __construct($config = array())
    {
        if (!class_exists('PDO')) {
            return false;
        }

        return parent::__construct($config);
    }

/**
 * Connects to a PDO-compatible server
 *
 * @return boolean True if server was connected
 * @throws PDOException
 */

    public function connect()
    {
        $config = $this->settings;

        $flags = array(
            PDO::ATTR_PERSISTENT => $config['persistent'],
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        );

        if (!empty($config['encoding'])) {
            $flags[PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET NAMES ' . $config['encoding'];
        }
        if (!empty($config['ssl_key']) && !empty($config['ssl_cert'])) {
            $flags[PDO::MYSQL_ATTR_SSL_KEY] = $config['ssl_key'];
            $flags[PDO::MYSQL_ATTR_SSL_CERT] = $config['ssl_cert'];
        }
        if (!empty($config['ssl_ca'])) {
            $flags[PDO::MYSQL_ATTR_SSL_CA] = $config['ssl_ca'];
        }
        if (empty($config['unix_socket'])) {
            $dsn = "mysql:host={$config['server']};port={$config['port']};dbname={$config['database']}";
        } else {
            $dsn = "mysql:unix_socket={$config['unix_socket']};dbname={$config['database']}";
        }

        $this->connection = new PDO(
            $dsn,
            $config['login'],
            $config['password'],
            $flags
        );
        if (!empty($config['settings'])) {
            foreach ($config['settings'] as $key => $value) {
                $this->execute("SET $key=$value");
            }
        }

        return (bool)$this->connection;
    }

    public function delete($item)
    {
        $sql = sprintf('DELETE FROM `%s` WHERE id = ?', $this->settings['table']);
        $sth = $this->connection->prepare($sql);
        $sth->bindParam(1, $item['id'], PDO::PARAM_INT);
        $sth->execute();
        return $sth->rowCount() == 1;
    }

    public function pop($queue = null)
    {
        $queue = $this->getQueue($queue);

        $sql = sprintf(
            'SELECT `id`, `data` FROM `%s` WHERE `queue` = ? and `locked` != 1 ORDER BY id asc LIMIT 1',
            $this->settings['table']
        );
        $sth = $this->connection->prepare($sql);
        $sth->bindParam(1, $queue, PDO::PARAM_STR);
        $sth->execute();
        $result = $sth->fetch(PDO::FETCH_ASSOC);

        if (empty($result)) {
            return null;
        }

        $sql = sprintf('UPDATE `%s` SET locked = 1 WHERE id = ?', $this->settings['table']);
        $sth = $this->connection->prepare($sql);
        $sth->bindParam(1, $result['id'], PDO::PARAM_INT);
        $sth->execute();
        if ($sth->rowCount() == 1) {
            return json_decode($result['data'], true);
        }

        return null;
    }

    public function push($class, $vars = array(), $queue = null)
    {
        $queue = $this->getQueue($queue);
        $data = json_encode(compact('class', 'vars'));
        $sql = sprintf('INSERT INTO `%s` (`data`, `queue`) VALUES (?, ?)', $this->settings['table']);
        $sth = $this->connection->prepare($sql);
        $sth->bindParam(1, $data, PDO::PARAM_STR);
        $sth->bindParam(2, $queue, PDO::PARAM_STR);
        $sth->execute();
        return $sth->rowCount() == 1;
    }

    public function release($item, $queue = null)
    {
        $sql = sprintf('UPDATE `%s` SET locked = 0 WHERE id = ?', $this->settings['table']);
        $sth = $this->connection->prepare($sql);
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
    protected function execute($sql, $params = array(), $prepareOptions = array())
    {
        $sql = trim($sql);
        if (preg_match('/^(?:CREATE|ALTER|DROP)\s+(?:TABLE|INDEX)/i', $sql)) {
            $statements = array_filter(explode(';', $sql));
            if (count($statements) > 1) {
                $result = array_map(array($this, 'execute'), $statements);
                return array_search(false, $result) === false;
            }
        }

        try {
            $query = $this->connection->prepare($sql, $prepareOptions);
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
