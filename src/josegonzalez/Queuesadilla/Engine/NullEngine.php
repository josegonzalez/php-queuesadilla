<?php

namespace josegonzalez\Queuesadilla\Engine;

use josegonzalez\Queuesadilla\Engine\Base;

class NullEngine extends Base
{
    public $return = true;

    public function connect()
    {
        return $this->return;
    }

    public function delete($item)
    {
        return $this->return;
    }

    public function pop($options = [])
    {
        $queue = $this->setting($options, 'queue');
        return $this->return;
    }

    public function push($class, $vars = [], $options = [])
    {
        $queue = $this->setting($options, 'queue');
        return $this->return;
    }

    public function release($item, $options = [])
    {
        $queue = $this->setting($options, 'queue');
        return $this->return;
    }

    public function queues()
    {
        return [];
    }
}
