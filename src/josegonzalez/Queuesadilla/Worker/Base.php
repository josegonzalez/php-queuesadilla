<?php

namespace josegonzalez\Queuesadilla\Worker;

use josegonzalez\Queuesadilla\Engine\EngineInterface;
use josegonzalez\Queuesadilla\Utility\LoggerTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

abstract class Base
{
    use LoggerTrait;

    protected $engine = null;

    protected $queue = 'default';

    protected $maxIterations = null;

    public function __construct(EngineInterface $engine, LoggerInterface $logger = null, $params = [])
    {
        $params = array_merge([
            'maxIterations' => null,
            'queue' => 'default',
        ], $params);

        $this->engine = $engine;
        $this->queue = $params['queue'];
        $this->maxIterations = $params['maxIterations'];
        $this->name = get_class($this->engine) . ' Worker';
        $this->setLogger($logger);
        return $this;
    }

    abstract public function work();
}
