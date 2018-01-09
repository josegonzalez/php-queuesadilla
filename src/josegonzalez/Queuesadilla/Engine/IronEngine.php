<?php

namespace josegonzalez\Queuesadilla\Engine;

use IronMQ;
use josegonzalez\Queuesadilla\Engine\Base;

class IronEngine extends Base
{
    protected $baseConfig = [
        'api_version' => 1,
        'delay' => null,
        'expires_in' => null,
        'host' => 'mq-aws-us-east-1.iron.io',
        'port' => 443,
        'project_id' => null,
        'protocol' => 'https',
        'queue' => 'default',
        'token' => null,
        'time_to_run' => 60,
    ];

    protected $ironSettings = [
        'api_version',
        'host',
        'port',
        'project_id',
        'protocol',
        'token',
    ];

    /**
     * {@inheritDoc}
     */
    public function connect()
    {
        $settings = [];
        foreach ($this->ironSettings as $key) {
            $settings[$key] = $this->config($key);
        }

        $this->connection = new IronMQ($settings);

        return (bool)$this->connection;
    }

    /**
     * {@inheritDoc}
     */
    public function acknowledge($item)
    {
        if (!parent::acknowledge($item)) {
            return false;
        }

        return $this->connection()->deleteMessage($item['queue'], $item['id']);
    }

    /**
     * {@inheritDoc}
     */
    public function reject($item)
    {
        return $this->acknowledge($item);
    }

    /**
     * {@inheritDoc}
     */
    public function pop($options = [])
    {
        $queue = $this->setting($options, 'queue');
        $item = $this->connection()->getMessage($queue);
        if (!$item) {
            return null;
        }

        $data = json_decode($item->body, true);

        return [
            'id' => $item->id,
            'class' => $data['class'],
            'args' => $data['args'],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function push($class, $args = [], $options = [])
    {
        $queue = $this->setting($options, 'queue');

        $item = json_encode(compact('class', 'args', 'queue'));

        return $this->connection()->postMessage($queue, $item, [
            "timeout" => $this->config('time_to_run'),
            "delay" => $this->config('delay'),
            "expires_in" => $this->config('expires_in'),
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function queues()
    {
        return $this->connection()->getQueues();
    }

    /**
     * {@inheritDoc}
     */
    public function release($item, $options = [])
    {
        $queue = $this->setting($options, 'queue');

        return $this->connection()->postMessage($queue, $item, [
            "timeout" => $this->config('time_to_run'),
            "delay" => $this->config('delay'),
            "expires_in" => $this->config('expires_in'),
        ]);
    }
}
