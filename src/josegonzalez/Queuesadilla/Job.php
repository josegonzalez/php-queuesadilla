<?php

namespace josegonzalez\Queuesadilla;

class Job
{
    const LOW = 4;
    const NORMAL = 3;
    const MEDIUM = 2;
    const HIGH = 1;
    const CRITICAL = 0;

    protected $item;

    protected $backend;

    public function __construct($item, $backend)
    {
        $this->item = $item;
        $this->backend = $backend;
    }

    public function attempts()
    {
        if (array_key_exists('attempts', $this->item)) {
            return $this->item['attempts'];
        }

        return $this->item['attempts'] = 0;
    }

    public function data($key = null, $default = null)
    {
        if ($key === null) {
            return $this->item['vars'];
        }

        if (array_key_exists($key, $this->item['vars'])) {
            return $this->item['vars'][$key];
        }

        return $default;
    }

    public function delete()
    {
        return $this->backend->delete($this->item);
    }

    public function item()
    {
        return $this->item;
    }

    public function release($delay = 0)
    {
        if (!isset($this->item['attempts'])) {
            $this->item['attempts'] = 0;
        }

        $this->item['attempts'] += 1;
        $this->item['delay'] = $delay;
        return $this->backend->release($this->item);
    }
}
