<?php

namespace josegonzalez\Queuesadilla;

class Queue
{
    public function __construct($engine)
    {
        $this->engine = $engine;
        return $this;
    }

    /**
     * Push a single job onto the queue.
     *
     * @param string $callable    a job callable
     * @param array  $vars        an array of data to set for the job
     * @param array  $options     an array of options for publishing the job
     *
     * @return boolean the result of the push
     **/
    public function push($callable, $vars = [], $options = [])
    {
        return $this->engine->push($callable, $vars, $options);
    }
}
