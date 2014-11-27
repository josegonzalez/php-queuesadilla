<?php

namespace josegonzalez\Queuesadilla\Engine;

use \IronMQ;
use \josegonzalez\Queuesadilla\Engine\Base;

class IronEngine extends Base
{
    protected $baseConfig = [
        'api_version' => 1,
        'delay' => null,
        'database' => 'database_name',  # unsupported
        'expires_in' => null,
        'user' => null,  # iron.project_id
        'pass' => null,  # iron.token
        'persistent' => true,  # unsupported
        'port' => 443,
        'priority' => 0,  # unsupported
        'protocol' => 'https',
        'queue' => 'default',
        'server' => 'mq-aws-us-east-1.iron.io',  # iron.host
        'table' => null,  # unsupported
        'time_to_run' => 60,  # iron.timeout
        'timeout' => 0,  # unsupported
    ];

    protected $ironSettings = [
        'protocol' => 'protocol',
        'server' => 'host',
        'port' => 'port',
        'api_version' => 'api_version',
        'user' => 'project_id',
        'pass' => 'token',
    ];

    public function __construct($config = [])
    {
        if (!class_exists('IronMQ')) {
            return false;
        }

        return parent::__construct($config);
    }

/**
 * Connects to a Iron server
 *
 * @return boolean True if BeanstalkD server was connected
 */
    public function connect()
    {
        $settings = [];
        foreach ($this->ironSettings as $key => $mapping) {
            $settings[$mapping] = $this->settings[$key];
        }

        $this->connection = new IronMQ($settings);
        return (bool)$this->connection;
    }

    public function delete($item)
    {
        $queue = $this->setting($options, 'queue');
        return $this->connection->deleteMessage($queue, $item['id']);
    }

    public function pop($options = [])
    {
        $queue = $this->setting($options, 'queue');
        $item = $this->connection->getMessage($queue);
        if (!$item) {
            return null;
        }

        $data = json_decode($item->body, true);
        return [
            'id' => $item->id,
            'class' => $data['class'],
            'vars' => $data['vars'],
        ];
    }

    public function push($class, $vars = [], $options = [])
    {
        $queue = $this->setting($options, 'queue');

        $item = json_encode(compact('class', 'vars'));
        return $this->connection->postMessage($queue, $item, [
            "timeout" => $this->settings['time_to_run'],
            "delay" => $this->settings['delay'],
            "expires_in" => $this->settings['expires_in']
        ]);
    }

    public function release($item, $options = [])
    {
        $queue = $this->setting($options, 'queue');
        return $this->connection->postMessage($queue, $item, [
            "timeout" => $this->settings['time_to_run'],
            "delay" => $this->settings['delay'],
            "expires_in" => $this->settings['expires_in']
        ]);
    }
}
