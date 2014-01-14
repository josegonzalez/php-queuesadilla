<?php

namespace josegonzalez\Queuesadilla\Backend;

use \Redis;
use \josegonzalez\Queuesadilla\Backend;

class RedisBackend extends Backend
{
    protected $baseConfig = array(
        'api_version' => 1,  # unsupported
        'delay' => null,  # unsupported
        'database' => null,
        'expires_in' => null,  # unsupported
        'login' => null,  # unsupported
        'password' => false,
        'persistent' => true,
        'port' => 6379,
        'priority' => 0,  # unsupported
        'protocol' => 'https',  # unsupported
        'queue' => 'default',
        'serializer' => null,
        'server' => '127.0.0.1',
        'table' => null,  # unsupported
        'time_to_run' => 60,  # unsupported
        'timeout' => 0,
    );

    public function __construct($config = array())
    {
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
        if (!is_array($item) || !isset($item['id'])) {
            return false;
        }

        return true;
    }

    public function pop($options = array())
    {
        $queue = $this->setting($options, 'queue');
        $item = $this->connection->lpop('queue:' . $queue);
        if (!$item) {
            return null;
        }

        return json_decode($item, true);
    }

    public function push($class, $vars = array(), $options = array())
    {
        $queue = $this->setting($options, 'queue');
        $this->connection->sadd('queues', $queue);

        $id = $this->id();
        return $this->connection->rpush('queue:' . $queue, json_encode(compact('id', 'class', 'vars')));
    }

    public function release($item, $options = array())
    {
        if (!is_array($item) || !isset($item['id'])) {
            return false;
        }

        $queue = $this->setting($options, 'queue');
        $this->connection->sadd('queues', $queue);
        return $this->connection->rpush('queue:' . $queue, json_encode($item));
    }

    protected function id()
    {
        return rand();
    }
}
