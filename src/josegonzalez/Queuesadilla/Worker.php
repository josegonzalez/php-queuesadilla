<?php

namespace josegonzalez\Queuesadilla;

abstract class Worker
{
    public function __construct($engine, $params = [])
    {
        $params = array_merge([
            'max_iterations' => null,
            'queue' => 'default',
        ], $params);

        $this->engine = $engine;
        $this->queue = $params['queue'];
        $this->max_iterations = $params['max_iterations'];

        $this->name = get_class($this->engine);
        if (preg_match('@\\\\([\w]+)$@', $this->name, $matches)) {
            $this->name = $matches[1];
        }

        $this->name = str_replace('Engine', '', $this->name) . ' Worker';
    }

    public function log($message)
    {
        printf("[%s] %s\n", $this->name, $message);
    }

    abstract public function work();
}
