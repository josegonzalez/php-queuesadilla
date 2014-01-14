<?php

namespace josegonzalez\Queuesadilla\Backend;

use \DateInterval;
use \DateTime;
use \josegonzalez\Queuesadilla\Backend;

class MemoryBackend extends Backend
{
    protected $baseConfig = array(
        'api_version' => 1,  # unsupported
        'delay' => null,
        'database' => 'database_name',  # unsupported
        'expires_in' => null,
        'login' => null,  # unsupported
        'password' => null,  # unsupported
        'persistent' => true,  # unsupported
        'port' => 0,  # unsupported
        'priority' => 0,  # unsupported
        'protocol' => 'https',  # unsupported
        'queue' => 'default',
        'server' => '127.0.0.1',  # unsupported
        'table' => null,  # unsupported
        'time_to_run' => 60,  # unsupported
        'timeout' => 0,  # unsupported
    );

    protected $queues = array();

    public function connect()
    {
        return true;
    }

    public function delete($item)
    {
        if (!is_array($item) || !isset($item['id'])) {
            return false;
        }

        $deleteFromQueue = false;
        foreach ($this->queues as $name => $queue) {
            foreach ($queue as $queueItem) {
                if ($queueItem['id'] === $item['id']) {
                    $deleteFromQueue = $name;
                    break 2;
                }
            }
        }

        if (!$deleteFromQueue) {
            return false;
        }

        $queue = array();
        foreach ($this->queues[$deleteFromQueue] as $queueItem) {
            if ($queueItem['id'] !== $item['id']) {
                array_push($queue, $queueItem);
            }
        }
        $this->queues[$deleteFromQueue] = $queue;
        return true;
    }

    public function pop($options = array())
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

            if ($itemId === null) {
                $itemId = $item['id'];
            } elseif ($itemId === $item['id']) {
                array_push($this->queues[$queue], $item);
                return null;
            }

            if (empty($item['options'])) {
                break;
            }

            $dt = new DateTime();
            if (!empty($item['options']['delay_until'])) {
                if ($dt < $item['options']['delay_until']) {
                    $this->queues[$queue][] = $item;
                    $item = null;
                    continue;
                }
            }

            if (!empty($item['options']['expires_at'])) {
                if ($dt > $item['options']['expires_at']) {
                    $item = null;
                    continue;
                }
            }
        }

        return $item;
    }

    public function push($class, $vars = array(), $options = array())
    {
        if (!is_array($options)) {
            $options = array('queue' => $options);
        }

        $options['queue'] = $this->setting($options, 'queue');
        $delay = $this->setting($options, 'delay');
        $expires_in = $this->setting($options, 'expires_in');
        $priority = $this->setting($options, 'priority');
        $this->requireQueue($options);
        $id = $this->id();

        if ($delay) {
            $dt = new DateTime();
            $options['delay_until'] = $dt->add(new DateInterval(sprintf('PT%sS', $delay)));
            unset($options['delay']);
        }

        if ($expires_in) {
            $dt = new DateTime();
            $options['expires_at'] = $dt->add(new DateInterval(sprintf('PT%sS', $expires_in)));
            unset($options['expires_in']);
        }

        $oldCount = count($this->queues[$options['queue']]);
        $newCount = array_push($this->queues[$options['queue']], compact('id', 'class', 'vars', 'options'));
        return $newCount === ($oldCount + 1);
    }

    public function release($item, $options = array())
    {
        $queue = $this->setting($options, 'queue');
        $this->requireQueue($options);

        return array_push($this->queues[$queue], $item) !== count($this->queues[$queue]);
    }

    protected function requireQueue($options)
    {
        $queue = $this->setting($options, 'queue');
        if (!isset($this->queues[$queue])) {
            $this->queues[$queue] = array();
        }
    }

    protected function id()
    {
        return rand();
    }
}
