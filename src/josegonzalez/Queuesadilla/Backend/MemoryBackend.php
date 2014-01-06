<?php

namespace josegonzalez\Queuesadilla\Backend;

use \josegonzalez\Queuesadilla\Backend;

class MemoryBackend extends Backend
{
    protected $baseConfig = array(
        'api_version' => 1,  # unsupported
        'delay' => 0,  # unsupported
        'database' => 'queuesadilla',  # unsupported
        'expires_in' => null,  # unsupported
        'login' => null,  # unsupported
        'password' => null,  # unsupported
        'persistent' => true,  # unsupported
        'port' => 0,  # unsupported
        'prefix' => null,  # unsupported
        'priority' => 0,  # unsupported
        'protocol' => 'https',  # unsupported
        'queue' => 'default',
        'serializer' => null,  # unsupported
        'server' => '127.0.0.1',  # unsupported
        'table' => null,  # unsupported
        'time_to_run' => 60,  # unsupported
        'timeout' => 0,  # unsupported
    );

    protected $queue = array();

    public function delete($item)
    {
        return true;
    }

    public function push($class, $vars = array(), $options = array())
    {
        $queue = $this->setting($options, 'queue');
        return array_push($this->queue, compact('class', 'vars')) !== count($this->queue);
    }

    public function release($item, $options = array())
    {
        $queue = $this->setting($options, 'queue');
        return array_push($this->queue, $item) !== count($this->queue);
    }

    public function pop($options = array())
    {
        $queue = $this->setting($options, 'queue');
        $item = array_shift($this->queue);
        if (!$item) {
            return null;
        }

        return $item;
    }
}
