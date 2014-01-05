<?php

namespace josegonzalez\Queuesadilla\Backend;

use \Socket_Beanstalk;
use \josegonzalez\Queuesadilla\Backend;

class BeanstalkBackend extends Backend
{
    protected $connection = null;

    protected $baseConfig = array(
        'delay' => 0,
        'persistent' => true,
        'port' => 11300,
        'priority' => 0,
        'queue' => 'default',
        'server' => '127.0.0.1',
        'time_to_run' => 60,
        'timeout' => 0,  # unsupported
    );

    protected $settings = null;

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

    public function pop($queue = null)
    {
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

    public function push($class, $vars = array(), $queue = null)
    {
        $queue = $this->getQueue($queue);
        $this->connection->choose($queue);
        return $this->connection->put(
            $this->settings['priority'],
            $this->settings['delay'],
            $this->settings['time_to_run'],
            json_encode(compact('class', 'vars'))
        );
    }

    public function release($item, $queue = null)
    {
        return $this->connection->bury($item['id']);
    }

    public function watch($queue = null)
    {
        $queue = $this->getQueue($queue);
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
