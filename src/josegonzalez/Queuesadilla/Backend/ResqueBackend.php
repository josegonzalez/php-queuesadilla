<?php

namespace josegonzalez\Queuesadilla\Backend;

use \Redis;
use \josegonzalez\Queuesadilla\Backend;

class ResqueBackend extends Backend
{
    protected $connection = null;

    protected $baseConfig = array(
        'prefix' => null,
        'server' => '127.0.0.1',
        'port' => 6379,
        'password' => false,
        'timeout' => 0,
        'persistent' => true,
        'queue' => 'default',
    );

    protected $settings = null;

    public function __construct($config = array())
    {
        if (!class_exists('Redis')) {
            return false;
        }

        $this->settings = array_merge($this->baseConfig, $config);
        return $this->connect();
    }

    public function push($class, $vars = array(), $queue = null)
    {
        $this->redisPush(compact('class', 'vars'), $queue);
    }

    public function release($item, $queue = null)
    {
        $this->redisPush($item, $queue);
    }

    public function pop($queue = null)
    {
        if ($queue === null) {
            $queue = $this->settings['queue'];
        }

        $item = $this->connection->lpop('queue:' . $queue);
        if (!$item) {
            return null;
        }

        return json_decode($item, true);
    }

    public function delete($item)
    {
    }

    protected function redisPush($item, $queue = null)
    {
        if ($queue === null) {
            $queue = $this->settings['queue'];
        }

        $this->connection->sadd('queues', $queue);
        $this->connection->rpush('queue:' . $queue, json_encode($item));
    }

/**
 * Connects to a Redis server
 *
 * @return boolean True if Redis server was connected
 */
    protected function connect()
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
        if ($return && $this->settings['password']) {
            $return = $this->connection->auth($this->settings['password']);
        }
        return $return;
    }
}
