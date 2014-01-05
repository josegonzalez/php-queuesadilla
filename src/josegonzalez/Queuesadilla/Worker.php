<?php

namespace josegonzalez\Queuesadilla;

class Worker
{
    public function __construct($backend, $params = array())
    {
        $params = array_merge(array(
            'max_iterations' => null,
            'queue' => null,
        ), $params);

        $this->backend = $backend;
        $this->queue = $params['queue'];
        $this->max_iterations = $params['max_iterations'];

        $this->name = get_class($this->backend);
        if (preg_match('@\\\\([\w]+)$@', $this->name, $matches)) {
            $this->name = $matches[1];
        }

        $this->name = str_replace('Backend', '', $this->name) . ' Worker';
    }

    public function log($message)
    {
        printf("[%s] %s\n", $this->name, $message);
    }

    abstract public function work();
}
