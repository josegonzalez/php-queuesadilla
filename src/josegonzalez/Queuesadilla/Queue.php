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
     * Bulk push an array of jobs onto the queue.
     *
     * @param  array $jobs    An array of callables
     * @param  array $vars    A set of data to set for each job
     * @param  array $options An array of options for publishing the job
     *
     * @return array An array of boolean values for each job
     **/
    public function bulk(array $jobs, $vars = [], $options = [])
    {
        return $this->engine->bulk($jobs, $vars, $options);
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
