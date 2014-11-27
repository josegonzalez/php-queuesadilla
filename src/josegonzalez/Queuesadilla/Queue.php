<?php

namespace josegonzalez\Queuesadilla;

class Queue
{
    public function __construct($engine)
    {
        $this->engine = $engine;
    }

    public function bulk($jobs, $vars = array(), $options = array())
    {
        return $this->engine->bulk($jobs, $vars, $options);
    }

    public function push($callable, $vars = array(), $options = array())
    {
        return $this->engine->push($callable, $vars, $options);
    }
}
