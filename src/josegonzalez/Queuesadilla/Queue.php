<?php

namespace josegonzalez\Queuesadilla;

class Queue
{
    public function __construct($backend)
    {
        $this->backend = $backend;
    }

    public function bulk($jobs, $vars = array(), $options = array())
    {
        return $this->backend->bulk($jobs, $vars, $options);
    }

    public function push($callable, $vars = array(), $options = array())
    {
        return $this->backend->push($callable, $vars, $options);
    }
}
