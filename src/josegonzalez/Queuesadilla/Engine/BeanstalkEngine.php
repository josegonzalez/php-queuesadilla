<?php

namespace josegonzalez\Queuesadilla\Engine;

use \DateInterval;
use \DateTime;
use \josegonzalez\Queuesadilla\Utility\Pheanstalk;
use \josegonzalez\Queuesadilla\Engine;
use \Pheanstalk\Command\DeleteCommand;
use \Pheanstalk\PheanstalkInterface;
use \Pheanstalk\Response;

class BeanstalkEngine extends Base
{
    protected $baseConfig = [
        'api_version' => 1,  # unsupported
        'delay' => null,
        'database' => 'database_name',  # unsupported
        'expires_in' => null,  # unsupported
        'user' => null,  # unsupported
        'pass' => null,  # unsupported
        'persistent' => true,
        'port' => 11300,
        'priority' => 0,
        'protocol' => 'https',  # unsupported
        'queue' => 'default',
        'host' => '127.0.0.1',
        'table' => null,  # unsupported
        'time_to_run' => 60,
        'timeout' => 0,  # unsupported
    ];

/**
 * Connects to a BeanstalkD server
 *
 * @return boolean True if BeanstalkD server was connected
 */
    public function connect()
    {
        $this->connection = new Pheanstalk(
            $this->settings['host'],
            $this->settings['port'],
            $this->settings['timeout']
        );
        return $this->connection->getConnection()->isServiceListening();
    }

    public function delete($item)
    {
        if (empty($item['job'])) {
            return false;
        }

        $response = $this->connection->deleteJob($item['job']);
        return $response->getResponseName() == 'DELETED';
    }

    public function pop($options = [])
    {
        $queue = $this->setting($options, 'queue');
        $this->connection->useTube($queue);
        $job = $this->connection->reserve(0);
        if (!$job) {
            return null;
        }

        $item = json_decode($job->getData(), true);
        $item['job'] = $job;
        $item['id'] = $job->getId();

        $dt = new DateTime();
        if (!empty($item['options']['expires_at']) && $dt > $item['options']['expires_at']) {
            return null;
        }

        return $item;
    }

    public function push($class, $vars = [], $options = [])
    {
        $queue = $this->setting($options, 'queue');
        $delay = $this->setting($options, 'delay');
        $expires_in = $this->setting($options, 'expires_in');
        $priority = $this->setting($options, 'priority');
        $time_to_run = $this->setting($options, 'time_to_run');

        $options = [];
        if ($expires_in !== null) {
            $dt = new DateTime();
            $options['expires_at'] = $dt->add(new DateInterval(sprintf('PT%sS', $expires_in)));
            unset($options['expires_in']);
        }

        unset($options['queue']);
        $this->connection->useTube($queue);
        try {
            $this->connection->put(
                json_encode(compact('class', 'vars', 'options')),
                $priority,
                $delay,
                $time_to_run
            );
        } catch (Exception $e) {
            // TODO: Proper logging
            return false;
        }

        return true;
    }

    public function release($item, $options = [])
    {
        $queue = $this->setting($options, 'queue');
        $delay = $this->setting($options, 'delay', PheanstalkInterface::DEFAULT_DELAY);
        $priority = $this->setting($options, 'priority', PheanstalkInterface::DEFAULT_PRIORITY);

        $this->connection->useTube($queue);
        $response = $this->connection->releaseJob($item['job'], $priority, $delay);
        return $response->getResponseName() == Response::RESPONSE_RELEASED;
    }

    public function queues()
    {
        return $this->connection->listTubes();
    }

    public function getJobClass()
    {
        return '\\josegonzalez\\Queuesadilla\\Job\\BeanstalkJob';
    }
}
