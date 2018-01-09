<?php

namespace josegonzalez\Queuesadilla\Engine;

use DateInterval;
use DateTime;
use josegonzalez\Queuesadilla\Utility\Pheanstalk;
use josegonzalez\Queuesadilla\Engine;
use Pheanstalk\Command\DeleteCommand;
use Pheanstalk\PheanstalkInterface;
use Pheanstalk\Response;

class BeanstalkEngine extends Base
{
    protected $baseConfig = [
        'delay' => null,
        'expires_in' => null,
        'port' => 11300,
        'priority' => 0,
        'queue' => 'default',
        'host' => '127.0.0.1',
        'time_to_run' => 60,
        'timeout' => 1,
    ];

    /**
     * {@inheritDoc}
     */
    public function getJobClass()
    {
        return '\\josegonzalez\\Queuesadilla\\Job\\BeanstalkJob';
    }

    /**
     * {@inheritDoc}
     */
    public function connect()
    {
        $this->connection = new Pheanstalk(
            $this->config('host'),
            $this->config('port'),
            $this->config('timeout')
        );

        return $this->connection->getConnection()->isServiceListening();
    }

    /**
     * {@inheritDoc}
     */
    public function acknowledge($item)
    {
        if (!parent::acknowledge($item)) {
            return false;
        }
        if (empty($item['job'])) {
            return false;
        }

        $response = $this->connection()->deleteJob($item['job']);

        return $response->getResponseName() == Response::RESPONSE_DELETED;
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
        $this->connection()->useTube($queue);
        $job = $this->connection()->reserve(0);
        if (!$job) {
            return null;
        }

        $item = json_decode($job->getData(), true);
        $item['job'] = $job;
        $item['id'] = $job->getId();

        $datetime = new DateTime;
        if (!empty($item['options']['expires_at']) && $datetime > $item['options']['expires_at']) {
            return null;
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
        $expiresIn = $this->setting($options, 'expires_in');
        $delay = $this->setting($options, 'delay', PheanstalkInterface::DEFAULT_DELAY);
        $priority = $this->setting($options, 'priority', PheanstalkInterface::DEFAULT_PRIORITY);
        $timeToRun = $this->setting($options, 'time_to_run', PheanstalkInterface::DEFAULT_TTR);

        $options = [];
        if ($expiresIn !== null) {
            $datetime = new DateTime;
            $options['expires_at'] = $datetime->add(new DateInterval(sprintf('PT%sS', $expiresIn)));
            unset($options['expires_in']);
        }

        unset($options['queue']);
        $this->connection()->useTube($queue);
        try {
            $item['options'] = $options;
            $this->lastJobId = $this->connection()->put(
                json_encode($item),
                $priority,
                $delay,
                $timeToRun
            );
        } catch (Exception $e) {
            // TODO: Proper logging
            return false;
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function queues()
    {
        return $this->connection()->listTubes();
    }

    /**
     * {@inheritDoc}
     */
    public function release($item, $options = [])
    {
        $queue = $this->setting($options, 'queue');
        $delay = $this->setting($options, 'delay', PheanstalkInterface::DEFAULT_DELAY);
        $priority = $this->setting($options, 'priority', PheanstalkInterface::DEFAULT_PRIORITY);

        $this->connection()->useTube($queue);
        $response = $this->connection()->releaseJob($item['job'], $priority, $delay);

        return $response->getResponseName() == Response::RESPONSE_RELEASED;
    }
}
