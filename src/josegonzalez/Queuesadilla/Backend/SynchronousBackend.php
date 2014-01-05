<?php

namespace josegonzalez\Queuesadilla\Backend;

use \josegonzalez\Queuesadilla\Backend\MemoryBackend;
use \josegonzalez\Queuesadilla\Worker\SequentialWorker;

class SynchronousBackend extends MemoryBackend
{
    public function push($class, $vars = array(), $queue = null)
    {
        parent::push($class, $vars, $queue);
        $worker = new SequentialWorker($this, array('max_iterations' => 1));
        $worker->work();
    }
}
