<?php

namespace josegonzalez\Queuesadilla\Engine;

use josegonzalez\Queuesadilla\Engine\Base;
use Redis;
use RedisException;

class RedisEngine extends Base
{
    protected $baseConfig = [
        'api_version' => 1,  # unsupported
        'delay' => null,  # unsupported
        'database' => null,
        'expires_in' => null,  # unsupported
        'user' => null,  # unsupported
        'pass' => false,
        'persistent' => true,
        'port' => 6379,
        'priority' => 0,  # unsupported
        'protocol' => 'https',  # unsupported
        'queue' => 'default',
        'host' => '127.0.0.1',
        'table' => null,  # unsupported
        'time_to_run' => 60,  # unsupported
        'timeout' => 0,
    ];

/**
 * Connects to a Redis server
 *
 * @return boolean True if Redis server was connected
 */
    public function connect()
    {
        $return = false;
        $connectMethod = 'connect';
        if (!empty($this->settings['persistent'])) {
            $connectMethod = 'pconnect';
        }

        try {
            $this->connection = $this->redisInstance();
            if ($this->connection) {
                $return = $this->connection->$connectMethod(
                    $this->settings['host'],
                    $this->settings['port'],
                    (int)$this->settings['timeout']
                );
            }
        } catch (RedisException $e) {
            return false;
        }

        if ($return && $this->settings['database'] !== null) {
            $return = $this->connection->select((int)$this->settings['database']);
        }

        if ($return && $this->settings['pass']) {
            $return = $this->connection->auth($this->settings['pass']);
        }

        return $return;
    }

    public function delete($item)
    {
        if (!is_array($item) || !isset($item['id'])) {
            return false;
        }

        return true;
    }

    public function pop($options = [])
    {
        $queue = $this->setting($options, 'queue');
        $item = $this->connection()->lpop('queue:' . $queue);
        if (!$item) {
            return null;
        }

        return json_decode($item, true);
    }

    public function push($class, $vars = [], $options = [])
    {
        $queue = $this->setting($options, 'queue');
        $this->requireQueue($options);

        $id = $this->jobId();
        return $this->connection()->rpush('queue:' . $queue, json_encode(compact('id', 'class', 'vars')));
    }

    public function release($item, $options = [])
    {
        if (!is_array($item) || !isset($item['id'])) {
            return false;
        }

        $queue = $this->setting($options, 'queue');
        $this->requireQueue($options);

        return $this->connection()->rpush('queue:' . $queue, json_encode($item));
    }

    public function queues()
    {
        return $this->connection()->smembers('queues');
    }

    protected function redisInstance()
    {
        return new Redis();
    }

    protected function requireQueue($options)
    {
        $queue = $this->setting($options, 'queue');
        $this->connection()->sadd('queues', $queue);
    }
}
