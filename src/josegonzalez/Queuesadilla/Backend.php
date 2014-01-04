<?php

namespace josegonzalez\Queuesadilla;

use \josegonzalez\Queuesadilla\Job;

abstract class Backend
{

    public function __construct($config)
    {
        return false;
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

    abstract public function push($class, $vars = array(), $queue = null);

    abstract public function release($item, $queue = null);

    abstract public function pop($queue = null);

    abstract public function delete($item);
}
