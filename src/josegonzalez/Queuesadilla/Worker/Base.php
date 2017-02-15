<?php

namespace josegonzalez\Queuesadilla\Worker;

use josegonzalez\Queuesadilla\Engine\EngineInterface;
use josegonzalez\Queuesadilla\Event\EventManagerTrait;
use josegonzalez\Queuesadilla\Event\MultiEventListener;
use josegonzalez\Queuesadilla\Utility\LoggerTrait;
use josegonzalez\Queuesadilla\Worker\Listener\StatsListener;
use Psr\Log\LoggerInterface;

abstract class Base extends MultiEventListener
{
    use EventManagerTrait;

    use LoggerTrait;

    protected $engine;

    protected $interval;

    protected $maxIterations;

    protected $maxRuntime;

    protected $queue;

    protected $stats;

    public function __construct(EngineInterface $engine, LoggerInterface $logger = null, $params = [])
    {
        $params = array_merge([
            'interval' => 1,
            'maxIterations' => null,
            'maxRuntime' => null,
            'queue' => 'default',
        ], $params);

        $this->engine = $engine;
        $this->queue = $params['queue'];
        $this->maxIterations = $params['maxIterations'];
        $this->interval = $params['interval'];
        $this->iterations = 0;
        $this->maxRuntime = $params['maxRuntime'];
        $this->runtime = 0;
        $this->name = get_class($this->engine) . ' Worker';
        $this->setLogger($logger);

        $this->StatsListener = new StatsListener;
        $this->attachListener($this->StatsListener);
        $this->attachListener($this);
        register_shutdown_function([&$this, 'shutdownHandler']);

        return $this;
    }

    public function implementedEvents()
    {
        return [];
    }

    public function stats()
    {
        return $this->StatsListener->stats;
    }

    public function shutdownHandler($signo = null)
    {
        $this->logger->info("Shutting down");

        $signals = [
            SIGQUIT => "SIGQUIT",
            SIGTERM => "SIGTERM",
            SIGINT => "SIGINT",
            SIGUSR1 => "SIGUSR1",
        ];

        if ($signo !== null) {
            $signal = $signals[$signo];
            $this->logger->info(sprintf("Received received %s... Shutting down", $signal));
        }
        $this->disconnect();

        $this->logger->info(sprintf(
            "Worker shutting down after running %d iterations in %ds",
            $this->iterations,
            $this->runtime
        ));

        return true;
    }

    abstract public function work();

    abstract protected function disconnect();
}
