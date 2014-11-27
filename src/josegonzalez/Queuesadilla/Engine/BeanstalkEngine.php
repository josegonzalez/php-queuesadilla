<?php

namespace josegonzalez\Queuesadilla\Engine;

use \Socket_Beanstalk;
use \josegonzalez\Queuesadilla\Engine;

class BeanstalkEngine extends Engine
{
    protected $baseConfig = array(
        'api_version' => 1,  # unsupported
        'delay' => null,
        'database' => 'database_name',  # unsupported
        'expires_in' => null,  # unsupported
        'user' => null,  # unsupported
        'pass' => null,  # unsupported
        'persistent' => true,
        'port' => 11300,
        'priority' => 0,
        'protocol' => 'https',  # unsupported
        'queue' => 'default',
        'server' => '127.0.0.1',
        'table' => null,  # unsupported
        'time_to_run' => 60,
        'timeout' => 0,  # unsupported
    );

    public function __construct($config = array())
    {
        if (!class_exists('Socket_Beanstalk')) {
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
        $this->connection = new Socket_Beanstalk($this->settings);
        return $this->connection->connect();
    }

    public function delete($item)
    {
        return $this->connection->delete($item['id']);
    }

    public function pop($options = array())
    {
        $queue = $this->setting($options, 'queue');
        $item = $this->connection->reserve();
        if (!$item) {
            return null;
        }

        $item['body'] = json_decode($item['body'], true);
        $item['class'] = $item['body']['class'];
        $item['vars'] = $item['vars'];
        unset($item['body']);

        return $item;
    }

    public function push($class, $vars = array(), $options = array())
    {
        $queue = $this->setting($options, 'queue');
        $this->connection->choose($queue);
        return $this->connection->put(
            $this->settings['priority'],
            $this->settings['delay'],
            $this->settings['time_to_run'],
            json_encode(compact('class', 'vars'))
        );
    }

    public function release($item, $options = array())
    {
        $queue = $this->setting($options, 'queue');
        return $this->connection->bury($item['id']);
    }

    public function watch($options = array())
    {
        $queue = $this->setting($options, 'queue');
        return $this->connection->watch($queue);
    }

    public function getJobClass()
    {
        return '\\josegonzalez\\Queuesadilla\\Job\\BeanstalkJob';
    }

    public function statsJob($item)
    {
        return $this->connection->statsJob($item['id']);
    }
}
