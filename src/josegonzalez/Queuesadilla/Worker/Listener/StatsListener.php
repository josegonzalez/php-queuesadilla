<?php

namespace josegonzalez\Queuesadilla\Worker\Listener;

use josegonzalez\Queuesadilla\Event\MultiEventListener;

/**
 * {@inheritDoc}
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class StatsListener extends MultiEventListener
{
    public $stats = null;

    public function __construct()
    {
        $this->stats = [
            'connectionFailed' => 0,
            'maxIterations' => 0,
            'maxRuntime' => 0,
            'seen' => 0,
            'empty' => 0,
            'exception' => 0,
            'invalid' => 0,
            'success' => 0,
            'failure' => 0,
        ];
    }

    public function implementedEvents()
    {
        return [
            'Worker.connectionFailed' => 'connectionFailed',
            'Worker.maxIterations' => 'maxIterations',
            'Worker.maxRuntime' => 'maxRuntime',
            'Worker.job.seen' => 'jobSeen',
            'Worker.job.empty' => 'jobEmpty',
            'Worker.job.invalid' => 'jobInvalid',
            'Worker.job.start' => 'jobStart',
            'Worker.job.exception' => 'jobException',
            'Worker.job.success' => 'jobSuccess',
            'Worker.job.failure' => 'jobFailure',
        ];
    }

    public function connectionFailed()
    {
        $this->stats['connectionFailed'] += 1;
    }

    public function maxIterations()
    {
        $this->stats['maxIterations'] += 1;
    }
    public function maxRuntime()
    {
        $this->stats['maxRuntime'] += 1;
    }
    public function jobSeen()
    {
        $this->stats['seen'] += 1;
    }
    public function jobEmpty()
    {
        $this->stats['empty'] += 1;
    }
    public function jobInvalid()
    {
        $this->stats['invalid'] += 1;
    }
    public function jobException()
    {
        $this->stats['exception'] += 1;
    }
    public function jobSuccess()
    {
        $this->stats['success'] += 1;
    }
    public function jobFailure()
    {
        $this->stats['failure'] += 1;
    }
}
