<?php

namespace josegonzalez\Queuesadilla;

abstract class Job
{
    protected $item;

    protected $backend;

    public function __construct($item, $backend)
    {
        $this->item = $item;
        $this->backend = $backend;
    }

    public function data($key, $default = null)
    {
        if (array_key_exists($key, $this->item['vars'])) {
            return $this->item['vars'][$key];
        }

        return $default;
    }

    public function release($delay = 0)
    {
        if (!isset($this->item['attempts'])) {
            $this->item['attempts'] = 0;
        }

        $this->item['attempts'] += 1;
        $this->item['delay'] = $delay;
        $this->backend->release($this->item);
    }

    abstract public function delete();
    abstract public function attempts();
}
