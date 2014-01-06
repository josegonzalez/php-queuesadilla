<?php

namespace josegonzalez\Queuesadilla\Backend;

use \josegonzalez\Queuesadilla\Backend\MemoryBackend;
use \josegonzalez\Queuesadilla\Worker\SequentialWorker;

class SynchronousBackend extends MemoryBackend
{
    public function push($class, $vars = array(), $options = array())
    {
        $queue = $this->setting($options, 'queue');
        parent::push($class, $vars, $queue);
        $worker = new SequentialWorker($this, array('max_iterations' => 1));
        return $worker->work();
    }
}
