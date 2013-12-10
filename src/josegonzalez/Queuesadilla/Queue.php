<?php

namespace josegonzalez\Queuesadilla;

class Queue
{
    public function __construct($backend)
    {
        $this->_backend = $backend;
    }

    public function bulk($jobs, $vars = array(), $queue = null)
    {
        $this->_backend->bulk($jobs, $vars, $queue);
    }

    public function push($callable, $vars = array(), $queue = null)
    {
        $this->_backend->push($callable, $vars, $queue);
    }
}
