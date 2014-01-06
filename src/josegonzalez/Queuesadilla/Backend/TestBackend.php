<?php

namespace josegonzalez\Queuesadilla\Backend;

use \josegonzalez\Queuesadilla\Backend;

class TestBackend extends Backend
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

    public function pop($options = array())
    {
        $queue = $this->setting($options, 'queue');
        return $this->return;
    }

    public function push($class, $vars = array(), $options = array())
    {
        $queue = $this->setting($options, 'queue');
        return $this->return;
    }

    public function release($item, $options = array())
    {
        $queue = $this->setting($options, 'queue');
        return $this->return;
    }
}
