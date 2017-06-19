<?php

namespace josegonzalez\Queuesadilla\Worker\Listener;

use josegonzalez\Queuesadilla\Event\MultiEventListener;
use josegonzalez\Queuesadilla\Utility\LoggerTrait;
use League\Event\AbstractEvent;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class DummyListener extends MultiEventListener
{
    use LoggerTrait;

    public function __construct(LoggerInterface $logger = null)
    {
        $this->setLogger($logger);
    }

    public function implementedEvents()
    {
        return [
            'Worker.connectionFailed' => 'perform',
            'Worker.maxIterations' => 'perform',
            'Worker.maxRuntime' => 'perform',
            'Worker.job.seen' => 'perform',
            'Worker.job.empty' => 'perform',
            'Worker.job.invalid' => 'perform',
            'Worker.job.start' => 'perform',
            'Worker.job.exception' => 'perform',
            'Worker.job.success' => 'perform',
            'Worker.job.failure' => 'perform',
        ];
    }

    public function perform(AbstractEvent $event)
    {
        $data = $event->data();
        $job = '';
        if (!empty($data['job'])) {
            $job = $data['job'];
        }

        $this->logger()->info(sprintf("%s: %s\n", $event->name(), json_encode($job)));
    }
}
