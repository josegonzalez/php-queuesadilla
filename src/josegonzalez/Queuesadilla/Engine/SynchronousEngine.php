<?php

namespace josegonzalez\Queuesadilla\Engine;

use josegonzalez\Queuesadilla\Engine\MemoryEngine;
use josegonzalez\Queuesadilla\Worker\SequentialWorker;

class SynchronousEngine extends MemoryEngine
{
    /**
     * {@inheritDoc}
     */
    public function push($class, $vars = [], $options = [])
    {
        parent::push($class, $vars, $options);
        $worker = $this->getWorker();
        return $worker->work();
    }

    /**
     * {@inheritDoc}
     */
    protected function getWorker()
    {
        return new SequentialWorker($this, $this->logger, ['maxIterations' => 1]);
    }
}
