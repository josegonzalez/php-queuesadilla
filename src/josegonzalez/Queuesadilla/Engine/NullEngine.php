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
        return $this->connection = $this->return;
    }

    /**
     * {@inheritDoc}
     */
    public function acknowledge($item)
    {
        if (!parent::acknowledge($item)) {
            return false;
        }

        return $this->return;
    }

    /**
     * {@inheritDoc}
     */
    public function reject($item)
    {
        return $this->acknowledge($item);
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
    public function push($item, $options = [])
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
