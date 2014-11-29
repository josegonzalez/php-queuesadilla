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

    protected $queues = [];

    /**
     * {@inheritDoc}
     */
    public function connect()
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function delete($item)
    {
        if (!is_array($item) || !isset($item['id'])) {
            return false;
        }

        $deleted = false;
        foreach ($this->queues as $name => $queue) {
            foreach ($queue as $i => $queueItem) {
                if ($queueItem['id'] === $item['id']) {
                    unset($this->queues[$name][$i]);
                    $deleted = true;
                    break 2;
                }
            }
        }
        return $deleted;
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
    public function push($class, $vars = [], $options = [])
    {
        if (!is_array($options)) {
            $options = ['queue' => $options];
        }

        $queue = $this->setting($options, 'queue');
        $delay = $this->setting($options, 'delay');
        $expiresIn = $this->setting($options, 'expires_in');
        $this->requireQueue($options);
        $jobId = $this->jobId();

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
        $newCount = array_push($this->queues[$queue], [
            'id' => $jobId,
            'class' => $class,
            'vars' => $vars,
            'options' => $options
        ]);
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
