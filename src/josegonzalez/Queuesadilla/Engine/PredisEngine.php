<?php

namespace josegonzalez\Queuesadilla\Engine;

use Exception;
use josegonzalez\Queuesadilla\Engine\RedisEngine;
use Predis\Client;
use Predis\Connection\ConnectionException;

class PredisEngine extends RedisEngine
{
    protected $persistentEnabled = false;

    /**
     * {@inheritDoc}
     */
    public function connect()
    {
        try {
            $return = parent::connect();
        } catch (Exception $e) {
            return false;
        }

        return !($return === false);
    }

    protected function evalSha($scriptSha, $item)
    {
        return (bool)$this->connection()->evalSha(
            $scriptSha,
            3,
            $item['queue'],
            rand(),
            $item['id']
        );
    }

    protected function redisInstance()
    {
        return new Client([
            'host' => $this->config('host'),
            'port' => $this->config('port'),
            'timeout' => (int)$this->config('timeout'),
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function release($item, $options = [])
    {
        if (isset($item['attempts']) && $item['attempts'] === 0) {
            return $this->reject($item);
        }

        return parent::release($item, $options);
    }
}
