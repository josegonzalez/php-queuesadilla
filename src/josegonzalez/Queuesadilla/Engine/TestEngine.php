<?php

namespace josegonzalez\Queuesadilla\Engine;

use \josegonzalez\Queuesadilla\Engine\Base;

class TestEngine extends Base
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
