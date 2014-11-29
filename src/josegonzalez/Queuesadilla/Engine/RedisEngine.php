<?php

namespace josegonzalez\Queuesadilla\Engine;

use josegonzalez\Queuesadilla\Engine\Base;
use Redis;
use RedisException;

class RedisEngine extends Base
{
    protected $baseConfig = [
        'database' => null,
        'pass' => false,
        'persistent' => true,
        'port' => 6379,
        'queue' => 'default',
        'host' => '127.0.0.1',
        'timeout' => 0,
    ];

    /**
     * {@inheritDoc}
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

    /**
     * {@inheritDoc}
     */
    public function delete($item)
    {
        if (!is_array($item) || !isset($item['id'])) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function pop($options = [])
    {
        $queue = $this->setting($options, 'queue');
        $item = $this->connection()->lpop('queue:' . $queue);
        if (!$item) {
            return null;
        }

        return json_decode($item, true);
    }

    /**
     * {@inheritDoc}
     */
    public function push($class, $vars = [], $options = [])
    {
        $queue = $this->setting($options, 'queue');
        $this->requireQueue($options);

        $jobId = $this->jobId();
        return $this->connection()->rpush('queue:' . $queue, json_encode([
            'id' => $jobId,
            'class' => $class,
            'vars' => $vars,
        ]));
    }

    /**
     * {@inheritDoc}
     */
    public function queues()
    {
        return $this->connection()->smembers('queues');
    }

    /**
     * {@inheritDoc}
     */
    public function release($item, $options = [])
    {
        if (!is_array($item) || !isset($item['id'])) {
            return false;
        }

        $queue = $this->setting($options, 'queue');
        $this->requireQueue($options);

        return $this->connection()->rpush('queue:' . $queue, json_encode($item));
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
