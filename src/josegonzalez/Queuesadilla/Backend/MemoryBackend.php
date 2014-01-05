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
    }

    public function push($class, $vars = array(), $queue = null)
    {
        array_push($this->queue, compact('class', 'vars'));
    }

    public function release($item, $queue = null)
    {
        array_push($this->queue, $item);
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
