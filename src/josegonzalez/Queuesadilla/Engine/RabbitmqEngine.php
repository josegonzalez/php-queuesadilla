<?php

namespace josegonzalez\Queuesadilla\Engine;

use Bunny\Client;
use Exception;
use josegonzalez\Queuesadilla\Engine\Base;

class RabbitmqEngine extends Base
{
    protected $baseConfig = [
        'database' => null,
        'user' => 'guest',
        'pass' => 'guest',
        'persistent' => true,
        'port' => 5672,
        'queue' => 'default',
        'host' => '127.0.0.1',
        'timeout' => 0,
        'insist' => false,
        'login_method' => 'AMQPLAIN',
        'login_response' => null,
        'locale' => 'en_US',
        'connection_timeout' => 3,
        'read_write_timeout' => 3,
        'context' => null,
        'keepalive' => false,
        'heartbeat' => 0,

        'passive_queue' => false,
        'durable_queue' => true,
        'exclusive' => false,
        'auto_delete_queue' => false,
        'nowait' => false,
        'arguments' => [],

        'exchange' => 'jobs',
        'type' => 'direct',
        'passive_exchange' => false,
        'durable_exchange' => true,
        'auto_delete_exchange' => false,

        'acknowledge' => true,

        'confirm' => false,
        'connect_attempt_delay' => 0.1,
        'connect_attempts' => 3,
        'mandatory_publish' => false,
        'immediate_publish' => false,
    ];

    public $channel;

    protected $handlerAttached = false;

    protected $queues = null;

    /**
     * {@inheritDoc}
     */
    public function connect()
    {
        $vhost = '/';
        $database = $this->config('database');
        if ($database !== null) {
            $vhost = '/' . $database;
        }
        try {
            $this->connection = new Client([
                'host' => $this->config('host'),
                'port' => $this->config('port'),
                'user' => $this->config('user'),
                'password' => $this->config('pass'),
                'vhost' => $vhost,
            ]);

            $this->connection->connect();
        } catch (Exception $e) {
            $this->connection = null;
            $this->channel = null;
            return false;
        }

        if (!$this->isConnected()) {
            $this->connection = null;
            $this->channel = null;
            return false;
        }

        $this->channel = $this->connection->channel();
        $this->channel->exchangeDeclare(
            $this->config('exchange'),
            $this->config('type'),
            $this->config('passive_exchange'),
            $this->config('durable_exchange'),
            $this->config('auto_delete_exchange')
        );
        $queue = $this->config('queue');
        $this->declareAndBindQueue($queue);

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function acknowledge($item)
    {
        if (!parent::acknowledge($item)) {
            return false;
        }
        if (isset($item['_message']) && $this->isConnected()) {
            $this->channel->ack($item['_message']);
            return true;
        }
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function reject($item)
    {
        if (isset($item['_message']) && $this->isConnected()) {
            $this->channel->reject($item['_message'], false);
            return true;
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function pop($options = [])
    {
        $item = null;
        $queue = $this->setting($options, 'queue');
        if ($this->isConnected()) {
            $item = $this->channel->get($queue);
        }
        if (!$item) {
            return null;
        }

        if ($this->setting($options, 'acknowledge')) {
            $this->channel->ack($item);
        }
        $data = json_decode($item->content, true);
        $data['_message'] = $item;
        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function push($item, $options = [])
    {
        $body = json_encode($item);

        $options = array_merge([
            'connect_attempts' => $this->config('connect_attempts'),
            'mandatory_publish' => $this->config('mandatory_publish'),
            'immediate_publish' => $this->config('immediate_publish'),
            'headers' => [],
        ], (array) $options);

        if (empty($options['headers']['content_type'])) {
            $options['headers'] = ['content_type' => 'application/json'];
        }

        if (!$this->isConnected()) {
            $this->tryToConnect($options['connect_attempts']);
        }

        if (!$this->isConnected()) {
            $this->logger()->info('Tried to publish without an AMQP connection');

            return false;
        }

        if ($this->config('confirm')) {
            $this->channel->txSelect();
        }

        $queue = $this->setting($options, 'queue');
        if ($this->setting($options, 'queue') !== $this->config('queue')) {
            $this->declareAndBindQueue($queue);
            $this->queues[] = $queue;
            $this->queues = array_unique($this->queues);
        }

        $this->channel->publish(
            $body,
            (array) $options['headers'],
            $this->config('exchange'),
            $queue,
            $this->setting($options, 'mandatory_publish'),
            $this->setting($options, 'immediate_publish')
        );

        if ($this->config('confirm')) {
            return $this->channel->txCommit();
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function queues()
    {
        if (empty($this->queues)) {
            $this->queues = [$this->config('queue')];
        }
        return $this->queues;
    }

    /**
     * {@inheritDoc}
     */
    public function release($item, $options = [])
    {
        if (!$this->isConnected()) {
            return false;
        }

        if (!empty($item)) {
            try {
                return (bool)$this->channel->reject($item['_delivery_tag'], true);
            } catch (\PhpAmqpLib\Exception\AMQPProtocolChannelException $e) {
                $this->logger()->info(sprintf('Error releasing message: %s', $e));
            }
        }
        return false;
    }

    public function attachHandler($options = [])
    {
        $options = array_merge([
            'consumer_tag' => '',
            'no_local' => false,
            'no_ack' => false,
            'exclusive' => false,
            'nowait' => false,
            'handler' => null,
            'arguments' => [],
        ], $options);

        if (empty($options['handler'])) {
            throw new \Exception('Invalid handler specified for RabbitmqEngine::pop()');
        }

        if (!$this->config('confirm')) {
            $this->channel->confirm_select();
        }
        $this->channel->qos(null, 1, null);
        return $this->channel->consume(
            $this->config('queue'),
            $this->config('routing_key'),
            $options['no_local'],
            $options['no_ack'],
            $options['exclusive'],
            $options['nowait'],
            $options['handler'],
            $options['arguments']
        );
    }

    public function canWork()
    {
        return count($this->channel->callbacks);
    }

    public function work()
    {
        $this->channel->wait();
    }

    protected function declareAndBindQueue($queue)
    {
        $routingKey = $queue;
        $this->channel->queueDeclare(
            $queue,
            $this->config('passive_queue'),
            $this->config('durable_queue'),
            $this->config('exclusive'),
            $this->config('auto_delete_queue'),
            $this->config('nowait'),
            $this->config('arguments')
        );
        $this->channel->queueBind(
            $queue,
            $this->config('exchange'),
            $routingKey
        );
    }

    /**
     * Attempts to connect to a host
     *
     * @param integer $attempts attempts to try to connect before failing
     *
     * @return void
     **/
    protected function tryToConnect($attempts = 3)
    {
        while ($attempts > 0) {
            $attempts--;
            if ($this->isConnected()) {
                return;
            }

            $this->connect();
            sleep($this->config('connect_attempt_delay'));
        }
    }

    public function isConnected()
    {
        return $this->connection !== null && $this->connection->isConnected();
    }
}
