<?php

namespace josegonzalez\Queuesadilla\Backend;

use \Redis;
use \josegonzalez\Queuesadilla\Backend;

class RedisBackend extends Backend
{
    protected $connection = null;

    protected $baseConfig = array(
        'database' => null,
        'password' => false,
        'persistent' => true,
        'port' => 6379,
        'prefix' => 'jobs:',
        'queue' => 'default',
        'serializer' => null,
        'server' => '127.0.0.1',
        'timeout' => 0,
    );

    protected $settings = null;

    public function __construct($config = array())
    {
        if (!class_exists('Redis')) {
            return false;
        }

        return parent::__construct($config);
    }

/**
 * Connects to a Redis server
 *
 * @return boolean True if Redis server was connected
 */
    public function connect()
    {
        $return = false;
        try {
            $this->connection = new Redis();
            if (empty($this->settings['persistent'])) {
                $return = $this->connection->connect(
                    $this->settings['server'],
                    $this->settings['port'],
                    $this->settings['timeout']
                );
            } else {
                $return = $this->connection->pconnect(
                    $this->settings['server'],
                    $this->settings['port'],
                    $this->settings['timeout']
                );
            }
        } catch (RedisException $e) {
            return false;
        }

        if ($return && $this->settings['database'] !== null) {
            $return = $this->connection->select((int)$this->settings['database']);
        }

        if ($return && $this->settings['prefix']) {
            $return = $this->connection->setOption(Redis::OPT_PREFIX, $this->settings['prefix']);
        }

        if ($return && $this->settings['serializer']) {
            $return = $this->connection->setOption(Redis::OPT_SERIALIZER, $this->settings['serializer']);
        }

        if ($return && $this->settings['password']) {
            $return = $this->connection->auth($this->settings['password']);
        }

        return $return;
    }

    public function delete($item)
    {
        return true;
    }

    public function pop($queue = null)
    {
        $queue = $this->getQueue($queue);
        $item = $this->connection->lpop('queue:' . $queue);
        if (!$item) {
            return null;
        }

        return json_decode($item, true);
    }

    public function push($class, $vars = array(), $queue = null)
    {
        $queue = $this->getQueue($queue);
        $this->connection->sadd('queues', $queue);
        return $this->connection->rpush('queue:' . $queue, json_encode(compact('class', 'vars')));
    }

    public function release($item, $queue = null)
    {
        $queue = $this->getQueue($queue);
        $this->connection->sadd('queues', $queue);
        return $this->connection->rpush('queue:' . $queue, json_encode($item));
    }
}
