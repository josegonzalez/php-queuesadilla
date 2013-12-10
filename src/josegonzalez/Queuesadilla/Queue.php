<?php

namespace josegonzalez\Queuesadilla;

class Queue
{
    public function __construct($backend)
    {
        $this->backend = $backend;
    }

    public function bulk($jobs, $vars = array(), $queue = null)
    {
        $this->backend->bulk($jobs, $vars, $queue);
    }

    public function push($callable, $vars = array(), $queue = null)
    {
        $this->backend->push($callable, $vars, $queue);
    }
}
