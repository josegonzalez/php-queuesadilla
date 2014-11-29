<?php

namespace josegonzalez\Queuesadilla\Engine;

use josegonzalez\Queuesadilla\Engine\EngineInterface;
use josegonzalez\Queuesadilla\Job;
use josegonzalez\Queuesadilla\Utility\DsnParserTrait;
use josegonzalez\Queuesadilla\Utility\LoggerTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

abstract class Base implements EngineInterface
{

    use DsnParserTrait;

    use LoggerTrait;

    protected $baseConfig = [
        'queue' => 'default',
    ];

    protected $connected = null;

    protected $settings = [];

    public $connection = null;

    public function __construct(LoggerInterface $logger = null, $config = [])
    {
        if (is_array($config) && !empty($config['url'])) {
            $config = array_merge($config, $this->parseDsn($config['url']));
        } elseif (is_string($config)) {
            $config = $this->parseDsn($config);
        }

        $this->setLogger($logger);
        $this->settings = $this->baseConfig;
        $this->config($config);
        return $this;
    }

    /**
     * Bulk push an array of jobs onto the queue.
     *
     * @param  array $jobs    An array of callables
     * @param  array $vars    A set of data to set for each job
     * @param  array $options An array of options for publishing the job
     *
     * @return array An array of boolean values for each job
     **/
    public function bulk($jobs, $vars = [], $options = [])
    {
        $queue = $this->setting($options, 'queue');
        $return = [];
        foreach ((array)$jobs as $callable) {
            $return[] = $this->push($callable, $vars, $queue);
        }

        return $return;
    }

    public function getJobClass()
    {
        return '\\josegonzalez\\Queuesadilla\\Job\\Base';
    }

    public function config($key = null, $value = null)
    {
        if (is_array($key)) {
            $this->settings = array_merge($this->settings, $key);
            $key = null;
        }

        if ($key === null) {
            return $this->settings;
        }

        if ($value === null) {
            if (isset($this->settings[$key])) {
                return $this->settings[$key];
            }

            return null;
        }

        return $this->settings[$key] = $value;
    }

    public function setting($settings, $key, $default = null)
    {
        if (!is_array($settings)) {
            $settings = ['queue' => $settings];
        }

        $settings = array_merge($this->settings, $settings);

        if (isset($settings[$key])) {
            $value = $settings[$key];
        } else {
            $value = $default;
        }

        return $value;
    }

    public function connection()
    {
        if ($this->connected === null || $this->connection === null) {
            $this->connected = $this->connect();
        }
        return $this->connection;
    }

    public function connected()
    {
        $this->connection();
        return $this->connected;
    }

    public function jobId()
    {
        return rand();
    }

    /**
     * Create a connection to the backend
     *
     * @return boolean
     */
    abstract public function connect();

    /**
     * Delete a message from the queue.
     *
     * @param  array  $item       an array of item data
     *
     * @return void
     */
    abstract public function delete($item);

    /**
     * Pop the next job off of the queue.
     *
     * @param array  $options     an array of options for popping a job from the queue
     *
     * @return array an array of item data
     */
    abstract public function pop($options = []);

    /**
     * Push a single job onto the queue.
     *
     * @param string $callable    a job callable
     * @param array  $vars        an array of data to set for the job
     * @param array  $options     an array of options for publishing the job
     *
     * @return boolean
     **/
    abstract public function push($class, $vars = [], $options = []);

    /**
     * Get a list of available queues
     *
     * @return array
     */
    abstract public function queues();

    /**
     * Release the job back into the queue.
     *
     * @param  array  $item       an array of item data
     *
     * @return boolean
     */
    abstract public function release($item, $options = []);
}
