<?php

namespace josegonzalez\Queuesadilla\Backend;

use \josegonzalez\Queuesadilla\Backend\MemoryBackend;
use \josegonzalez\Queuesadilla\Worker\SequentialWorker;

class SynchronousBackend extends MemoryBackend
{
    public function push($class, $vars = array(), $options = array())
    {
        if (!parent::push($class, $vars, $options)) {
            return false;
        }
        $worker = $this->getWorker();
        return $worker->work();
    }

    protected function getWorker()
    {
        return new SequentialWorker($this, array('max_iterations' => 1));
    }
}
