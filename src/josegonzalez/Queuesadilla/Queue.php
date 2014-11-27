<?php

namespace josegonzalez\Queuesadilla;

class Queue
{
    public function __construct($engine)
    {
        $this->engine = $engine;
    }

    public function bulk($jobs, $vars = [], $options = [])
    {
        return $this->engine->bulk($jobs, $vars, $options);
    }

    public function push($callable, $vars = [], $options = [])
    {
        return $this->engine->push($callable, $vars, $options);
    }
}
