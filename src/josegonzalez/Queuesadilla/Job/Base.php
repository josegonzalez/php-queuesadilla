<?php

namespace josegonzalez\Queuesadilla\Job;

class Base
{
    const LOW = 4;
    const NORMAL = 3;
    const MEDIUM = 2;
    const HIGH = 1;
    const CRITICAL = 0;

    protected $item;

    protected $engine;

    public function __construct($item, $engine)
    {
        $this->item = $item;
        $this->engine = $engine;
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
        return $this->engine->delete($this->item);
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
        return $this->engine->release($this->item);
    }
}
