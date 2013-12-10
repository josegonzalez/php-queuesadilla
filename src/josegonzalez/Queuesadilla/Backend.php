<?php

namespace josegonzalez\Queuesadilla;

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
        $classname = get_class($this);

        if (preg_match('@\\\\([\w]+)$@', $classname, $matches)) {
                $classname = $matches[1];
        }

        return '\\josegonzalez\\Queuesadilla\\Job\\' . str_replace('Backend', 'Job', $classname);
    }

    abstract public function push($class, $vars = array(), $queue = null);

    abstract public function release($item, $queue = null);

    abstract public function pop($queue = null);

    abstract public function delete($item);
}
