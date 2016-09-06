<?php

namespace josegonzalez\Queuesadilla\Engine;

use Exception;
use josegonzalez\Queuesadilla\Engine\Base;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

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
        'arguments' => null,
        'ticket' => null,

        'exchange' => 'jobs',
        'type' => 'direct',
        'passive_exchange' => false,
        'durable_exchange' => true,
        'auto_delete_exchange' => false,

        'confirm' => false,
        'routing_key' => 'jobs',
        'connect_attempt_delay' => 0.1,
        'connect_attempts' => 3,
        'mandatory_publish' => false,
        'immediate_publish' => false,
        'ticket_publish' => null,

    ];

    public $channel;

    protected $handlerAttached = false;

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
            $this->connection = new AMQPStreamConnection(
                $this->config('host'),
                $this->config('port'),
                $this->config('user'),
                $this->config('pass'),
                $vhost,
                $this->config('insist'),
                $this->config('login_method'),
                $this->config('login_response'),
                $this->config('locale'),
                $this->config('connection_timeout'),
                $this->config('read_write_timeout'),
                $this->config('context'),
                $this->config('keepalive'),
                $this->config('heartbeat')
            );
        } catch (Exception $e) {
            $this->connection = null;
            return false;
        }

        if (!$this->connection->isConnected()) {
            $this->connection = null;
            return false;
        }

        $this->channel = $this->connection->channel();
        $this->channel->queue_declare(
            $this->config('queue'),
            $this->config('passive_queue'),
            $this->config('durable_queue'),
            $this->config('exclusive'),
            $this->config('auto_delete_queue'),
            $this->config('nowait'),
            $this->config('arguments'),
            $this->config('ticket')
        );

        $this->channel->exchange_declare(
            $this->config('exchange'),
            $this->config('type'),
            $this->config('passive_exchange'),
            $this->config('durable_exchange'),
            $this->config('auto_delete_exchange')
        );
        $this->channel->queue_bind(
            $this->config('queue'),
            $this->config('exchange'),
            $this->config('routing_key')
        );

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
        if ($this->isConnected()) {
            return $this->channel->basic_ack($item['_delivery_tag']);
        }
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function reject($item)
    {
        if ($this->isConnected()) {
            return $this->channel->basic_reject($item['_delivery_tag'], false);
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function pop($options = [])
    {
        return null;
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
            'ticket_publish' => $this->config('ticket_publish'),
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
            $this->channel->tx_select();
        }

        $this->channel->basic_publish(
            new AMQPMessage($body, (array) $options['headers']),
            $this->config('exchange'),
            $this->config('routing_key'),
            $this->setting($options, 'mandatory_publish'),
            $this->setting($options, 'immediate_publish'),
            $this->setting($options, 'ticket_publish')
        );

        if ($this->config('confirm')) {
            return $this->channel->tx_commit();
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function queues()
    {
        return [$this->config('queue')];
    }

    /**
     * {@inheritDoc}
     */
    public function release($item, $options = [])
    {
        if ($this->isConnected()) {
            return (bool)$this->channel->basic_reject($item['_delivery_tag'], true);
        }

        return true;
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
            'ticket' => null,
            'arguments' => [],
        ], $options);

        if (empty($options['handler'])) {
            throw new \Exception('Invalid handler specified for RabbitmqEngine::pop()');
        }

        if (!$this->config('confirm')) {
            $this->channel->confirm_select();
        }
        $this->channel->basic_qos(null, 1, null);
        return $this->channel->basic_consume(
            $this->config('queue'),
            $this->config('routing_key'),
            $options['no_local'],
            $options['no_ack'],
            $options['exclusive'],
            $options['nowait'],
            $options['handler'],
            $options['ticket'],
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

    protected function isConnected()
    {
        return $this->connection !== null && $this->connection->isConnected();
    }
}
