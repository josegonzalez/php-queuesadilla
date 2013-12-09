<?php

namespace Queuesadilla\Backend;

use \Queuesadilla\Backend\MemoryBackend;
use \Queuesadilla\Worker;

class SynchronousBackend extends MemoryBackend
{
    public function push($class, $vars = array(), $queue = null)
    {
        parent::push($class, $vars, $queue);
        $worker = new Worker($this, array('max_iterations' => 1));
        $worker->work();
    }
}
