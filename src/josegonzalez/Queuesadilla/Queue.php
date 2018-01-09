<?php

namespace josegonzalez\Queuesadilla;

use josegonzalez\Queuesadilla\Event\EventManagerTrait;

class Queue
{
    use EventManagerTrait;

    public function __construct($engine)
    {
        $this->engine = $engine;

        return $this;
    }

    /**
     * Push a single job onto the queue.
     *
     * @param string $callable    a job callable
     * @param array  $args        an array of data to set for the job
     * @param array  $options     an array of options for publishing the job
     *
     * @return boolean the result of the push
     **/
    public function push($callable, $args = [], $options = [])
    {
        $queue = $this->engine->setting($options, 'queue');
        $item = [
            'queue' => $queue,
            'class' => $callable,
            'args'  => [$args],
            'id'    => md5(uniqid('', true)),
            'queue_time' => microtime(true),
        ];
        $success = $this->engine->push($item, $options);

        unset($item['id']);
        if ($success) {
            $item['id'] = $this->engine->lastJobId();
        }

        $item['args'] = $args;
        $this->dispatchEvent('Queue.afterEnqueue', [
            'item' => $item,
            'success' => $success,
        ]);

        return $success;
    }
}
