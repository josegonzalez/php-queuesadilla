<?php

namespace josegonzalez\Queuesadilla\Worker;

use josegonzalez\Queuesadilla\Engine\EngineInterface;
use josegonzalez\Queuesadilla\Event\EventManagerTrait;
use josegonzalez\Queuesadilla\Utility\LoggerTrait;
use josegonzalez\Queuesadilla\Worker\Listener\StatsListener;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

abstract class Base
{
    use EventManagerTrait;

    use LoggerTrait;

    protected $engine;

    protected $maxIterations;

    protected $queue;

    protected $stats;

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

        $this->StatsListener = new StatsListener;
        $this->attachListener($this->StatsListener);
        return $this;
    }

    public function stats()
    {
        return $this->StatsListener->stats();
    }

    abstract public function work();
}
