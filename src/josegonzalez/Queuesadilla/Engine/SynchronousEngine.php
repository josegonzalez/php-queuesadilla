<?php

namespace josegonzalez\Queuesadilla\Engine;

use \josegonzalez\Queuesadilla\Engine\MemoryEngine;
use \josegonzalez\Queuesadilla\Worker\SequentialWorker;

class SynchronousEngine extends MemoryEngine
{
    public function push($class, $vars = [], $options = [])
    {
        parent::push($class, $vars, $options);
        $worker = $this->getWorker();
        return $worker->work();
    }

    protected function getWorker()
    {
        return new SequentialWorker($this, array('max_iterations' => 1));
    }
}
