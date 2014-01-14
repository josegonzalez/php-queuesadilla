<?php

namespace josegonzalez\Queuesadilla\Backend;

use \IronMQ;
use \josegonzalez\Queuesadilla\Backend;

class IronBackend extends Backend
{
    protected $baseConfig = array(
        'api_version' => 1,
        'delay' => null,
        'database' => 'database_name',  # unsupported
        'expires_in' => null,
        'login' => null,  # iron.project_id
        'password' => null,  # iron.token
        'persistent' => true,  # unsupported
        'port' => 443,
        'priority' => 0,  # unsupported
        'protocol' => 'https',
        'queue' => 'default',
        'serializer' => null,  # unsupported
        'server' => 'mq-aws-us-east-1.iron.io',  # iron.host
        'table' => null,  # unsupported
        'time_to_run' => 60,  # iron.timeout
        'timeout' => 0,  # unsupported
    );

    protected $ironSettings = array(
        'protocol' => 'protocol',
        'server' => 'host',
        'port' => 'port',
        'api_version' => 'api_version',
        'login' => 'project_id',
        'password' => 'token',
    );

    public function __construct($config = array())
    {
        if (!class_exists('IronMQ')) {
            return false;
        }

        return parent::__construct($config);
    }

/**
 * Connects to a BeanstalkD server
 *
 * @return boolean True if BeanstalkD server was connected
 */
    public function connect()
    {
        $settings = array();
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

    public function pop($options = array())
    {
        $queue = $this->setting($options, 'queue');
        $item = $this->connection->getMessage($queue);
        if (!$item) {
            return null;
        }

        $data = json_decode($item->body, true);
        return array(
            'id' => $item->id,
            'class' => $data['class'],
            'vars' => $data['vars'],
        );
    }

    public function push($class, $vars = array(), $options = array())
    {
        $queue = $this->setting($options, 'queue');

        $item = json_encode(compact('class', 'vars'));
        return $this->connection->postMessage($queue, $item, array(
            "timeout" => $this->settings['time_to_run'],
            "delay" => $this->settings['delay'],
            "expires_in" => $this->settings['expires_in']
        ));
    }

    public function release($item, $options = array())
    {
        $queue = $this->setting($options, 'queue');
        return $this->connection->postMessage($queue, $item, array(
            "timeout" => $this->settings['time_to_run'],
            "delay" => $this->settings['delay'],
            "expires_in" => $this->settings['expires_in']
        ));
    }
}
