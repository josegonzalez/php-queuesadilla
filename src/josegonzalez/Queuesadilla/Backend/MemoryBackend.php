<?php

namespace josegonzalez\Queuesadilla\Backend;

use \josegonzalez\Queuesadilla\Backend;

class MemoryBackend extends Backend
{
    protected $baseConfig = array(
        'queue' => 'default',
    );

    protected $queue = array();

    protected $settings = null;

    public function push($class, $vars = array(), $queue = null)
    {
        $this->memoryPush(compact('class', 'vars'), $queue);
    }

    public function release($item, $queue = null)
    {
        $this->memoryPush($item, $queue);
    }

    public function pop($queue = null)
    {
        $item = array_shift($this->queue);
        if (!$item) {
            return null;
        }

        return $item;
    }

    public function delete($item)
    {
    }

    protected function memoryPush($item, $queue = null)
    {
        array_push($this->queue, $item);
    }
}
