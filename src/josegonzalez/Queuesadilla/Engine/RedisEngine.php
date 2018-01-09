<?php

namespace josegonzalez\Queuesadilla\Engine;

use josegonzalez\Queuesadilla\Engine\Base;
use Redis;
use Exception;

class RedisEngine extends Base
{
    protected $persistentEnabled = true;

    protected $baseConfig = [
        'database' => null,
        'pass' => false,
        'persistent' => true,
        'port' => 6379,
        'queue' => 'default',
        'host' => '127.0.0.1',
        'timeout' => 0,
    ];

    /**
     * {@inheritDoc}
     */
    public function connect()
    {
        $return = false;
        $connectMethod = 'connect';
        if ($this->config('persistent') && $this->persistentEnabled) {
            $connectMethod = 'pconnect';
        }

        try {
            $this->connection = $this->redisInstance();
            if ($this->connection) {
                $return = $this->connection->$connectMethod(
                    $this->config('host'),
                    $this->config('port'),
                    (int)$this->config('timeout')
                );
            }
        } catch (Exception $e) {
            return false;
        }

        if ($return && $this->config('database') !== null) {
            $return = $this->connection->select((int)$this->config('database'));
        }

        if ($return && $this->config('pass')) {
            $return = $this->connection->auth($this->config('pass'));
        }

        return $return;
    }

    /**
     * {@inheritDoc}
     */
    public function acknowledge($item)
    {
        if (!parent::acknowledge($item)) {
            return false;
        }

        $script = $this->getRemoveScript();
        $exists = $this->ensureRemoveScript();
        if (!$exists) {
            return false;
        }

        return $this->evalSha(sha1($script), $item);
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
        $item = $this->connection()->lpop('queue:' . $queue);
        if (!$item) {
            return null;
        }

        return json_decode($item, true);
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
        $this->requireQueue($options);

        unset($options['queue']);
        $item['options'] = $options;
        $success = (bool)$this->connection()->rpush('queue:' . $queue, json_encode($item));
        if ($success) {
            $this->lastJobId = $item['id'];
        }

        return $success;
    }

    /**
     * {@inheritDoc}
     */
    public function queues()
    {
        return $this->connection()->smembers('queues');
    }

    /**
     * {@inheritDoc}
     */
    public function release($item, $options = [])
    {
        if (!is_array($item) || !isset($item['id'])) {
            return false;
        }

        $queue = $this->setting($options, 'queue');
        $this->requireQueue($options);

        return $this->connection()->rpush('queue:' . $queue, json_encode($item));
    }

    protected function ensureRemoveScript()
    {
        $script = $this->getRemoveScript();
        $exists = $this->connection()->script('exists', sha1($script));
        if (!empty($exists[0])) {
            return $exists[0];
        }

        return $this->connection()->script('load', $script);
    }

    protected function evalSha($scriptSha, $item)
    {
        return (bool)$this->connection()->evalSha($scriptSha, [
            $item['queue'],
            rand(),
            $item['id'],
        ], 3);
    }

    protected function getRemoveScript()
    {
        $script = <<<EOF
-- KEYS[1]: The queue to work on
-- KEYS[2]: A random number
-- KEYS[3]: The id of the message to delete
local originalQueue = 'queue:'..KEYS[1]
local tempQueue = originalQueue..':temp:'..KEYS[2]
local requeueQueue = tempQueue..':requeue'
local deleted = false
local itemId = KEYS[3]
while true do
    local str = redis.pcall('rpoplpush', originalQueue, tempQueue)
    if str == nil or str == '' or str == false then
        break
    end

    local item = cjson.decode(str)
    if tostring(item["id"]) == itemId then
        deleted = true
        break
    else
        redis.pcall('rpoplpush', tempQueue, requeueQueue)
    end
end

while true do
    local str = redis.pcall('rpoplpush', requeueQueue, originalQueue)
    if str == nil or str == '' or str == false then
        break
    end
end

redis.pcall('del', requeueQueue)
redis.pcall('del', tempQueue)
return deleted
EOF;

        return trim($script);
    }

    protected function redisInstance()
    {
        return new Redis();
    }

    protected function requireQueue($options)
    {
        $queue = $this->setting($options, 'queue');
        $this->connection()->sadd('queues', $queue);
    }
}
