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

    public function delete($item)
    {
        return true;
    }

    public function push($class, $vars = array(), $queue = null)
    {
        return array_push($this->queue, compact('class', 'vars')) !== count($this->queue);
    }

    public function release($item, $queue = null)
    {
        return array_push($this->queue, $item) !== count($this->queue);
    }

    public function pop($queue = null)
    {
        $item = array_shift($this->queue);
        if (!$item) {
            return null;
        }

        return $item;
    }
}
