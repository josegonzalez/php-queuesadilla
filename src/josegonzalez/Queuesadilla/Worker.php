<?php

namespace josegonzalez\Queuesadilla;

use \josegonzalez\Queuesadilla\Engine\EngineInterface;
use \Psr\Log\LoggerInterface;

abstract class Worker
{
    public function __construct(EngineInterface $engine, LoggerInterface $logger = null, $params = [])
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
        $this->logger = $logger;
    }

    abstract public function work();
}
