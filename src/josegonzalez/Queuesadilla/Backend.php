<?php

namespace josegonzalez\Queuesadilla;

use \josegonzalez\Queuesadilla\Job;

abstract class Backend
{
    protected $baseConfig = array();

    protected $connection = null;

    protected $settings = array();

    public function __construct($config)
    {
        $this->settings = array_merge($this->baseConfig, $config);
        return $this->connect();
    }

    public function bulk($jobs, $vars = array(), $queue = null)
    {
        foreach ((array)$jobs as $callable) {
            $this->push($callable, $vars, $queue);
        }
    }

    public function getJobClass()
    {
        return '\\josegonzalez\\Queuesadilla\\Job';
    }

    public function getQueue($queue = null)
    {
        if ($queue === null) {
            $queue = $this->settings['queue'];
        }

        return $queue;
    }

    public function watch($queue = null)
    {
        return true;
    }

    abstract protected function connect();

    abstract public function delete($item);

    abstract public function pop($queue = null);

    abstract public function push($class, $vars = array(), $queue = null);

    abstract public function release($item, $queue = null);
}
