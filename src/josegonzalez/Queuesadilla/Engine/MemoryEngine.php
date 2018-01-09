<?php

namespace josegonzalez\Queuesadilla\Engine;

use DateInterval;
use DateTime;
use josegonzalez\Queuesadilla\Engine\Base;

class MemoryEngine extends Base
{
    protected $baseConfig = [
        'delay' => null,
        'expires_in' => null,
        'queue' => 'default',
    ];

    protected $queues = [
        'default' => [],
    ];

    /**
     * {@inheritDoc}
     */
    public function connect()
    {
        return $this->connection = true;
    }

    /**
     * {@inheritDoc}
     */
    public function acknowledge($item)
    {
        if (!parent::acknowledge($item)) {
            return false;
        }

        $queue = $item['queue'];
        if (!isset($this->queues[$queue])) {
            return false;
        }

        $deleted = false;
        foreach ($this->queues[$queue] as $i => $queueItem) {
            if ($queueItem['id'] === $item['id']) {
                unset($this->queues[$queue][$i]);
                $deleted = true;
                break;
            }
        }

        return $deleted;
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
        $queue = $this->setting($options, 'queue');
        $this->requireQueue($options);

        $itemId = null;
        $item = null;
        while ($item === null) {
            $item = array_shift($this->queues[$queue]);
            if (!$item) {
                return null;
            }

            if ($itemId === $item['id']) {
                array_push($this->queues[$queue], $item);

                return null;
            }

            if ($itemId === null) {
                $itemId = $item['id'];
            }

            if (empty($item['options'])) {
                break;
            }

            if ($this->shouldDelay($item)) {
                $this->queues[$queue][] = $item;
                $item = null;
                continue;
            }

            if ($this->shouldExpire($item)) {
                $item = null;
                continue;
            }
        }

        return $item;
    }

    /**
     * {@inheritDoc}
     */
    public function push($item, $options = [])
    {
        if (!is_array($options)) {
            $options = ['queue' => $options];
        }

        $queue = $this->setting($options, 'queue');
        $delay = $this->setting($options, 'delay');
        $expiresIn = $this->setting($options, 'expires_in');
        $this->requireQueue($options);

        if ($delay !== null) {
            $datetime = new DateTime;
            $options['delay_until'] = $datetime->add(new DateInterval(sprintf('PT%sS', $delay)));
            unset($options['delay']);
        }

        if ($expiresIn !== null) {
            $datetime = new DateTime;
            $options['expires_at'] = $datetime->add(new DateInterval(sprintf('PT%sS', $expiresIn)));
            unset($options['expires_in']);
        }

        unset($options['queue']);
        $oldCount = count($this->queues[$queue]);
        $item['options'] = $options;
        $newCount = array_push($this->queues[$queue], $item);

        if ($newCount === ($oldCount + 1)) {
            $this->lastJobId = $item['id'];
        }

        return $newCount === ($oldCount + 1);
    }

    /**
     * {@inheritDoc}
     */
    public function queues()
    {
        return array_keys($this->queues);
    }

    /**
     * {@inheritDoc}
     */
    public function release($item, $options = [])
    {
        $queue = $this->setting($options, 'queue');
        $this->requireQueue($options);

        return array_push($this->queues[$queue], $item) !== count($this->queues[$queue]);
    }

    protected function requireQueue($options)
    {
        $queue = $this->setting($options, 'queue');
        if (!isset($this->queues[$queue])) {
            $this->queues[$queue] = [];
        }
    }

    protected function shouldDelay($item)
    {
        $datetime = new DateTime;
        if (!empty($item['options']['delay_until']) && $datetime < $item['options']['delay_until']) {
            return true;
        }

        return false;
    }

    protected function shouldExpire($item)
    {
        $datetime = new DateTime;
        if (!empty($item['options']['expires_at']) && $datetime > $item['options']['expires_at']) {
            return true;
        }

        return false;
    }
}
