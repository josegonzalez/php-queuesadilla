<?php

namespace josegonzalez\Queuesadilla\Engine;

use josegonzalez\Queuesadilla\Engine\Base;

class NullEngine extends Base
{
    public $return = true;

    /**
     * {@inheritDoc}
     */
    public function connect()
    {
        return $this->connected = $this->return;
    }

    /**
     * {@inheritDoc}
     */
    public function delete($item)
    {
        return $this->return;
    }

    /**
     * {@inheritDoc}
     */
    public function pop($options = [])
    {
        return $this->return;
    }

    /**
     * {@inheritDoc}
     */
    public function push($class, $vars = [], $options = [])
    {
        $this->lastJobId = $this->return;
        return $this->return;
    }

    /**
     * {@inheritDoc}
     */
    public function queues()
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function release($item, $options = [])
    {
        return $this->return;
    }
}
