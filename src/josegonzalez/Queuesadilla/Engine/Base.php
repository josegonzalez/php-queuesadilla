<?php

namespace josegonzalez\Queuesadilla\Engine;

use \josegonzalez\Queuesadilla\Engine\EngineInterface;
use \josegonzalez\Queuesadilla\Job;
use \josegonzalez\Queuesadilla\Utility\DsnParserTrait;

abstract class Base implements EngineInterface
{

    use DsnParserTrait;

    protected $baseConfig = [
        'queue' => 'default',
    ];

    protected $connected = false;

    protected $settings = [];

    public $connection = null;

    public function __construct($config = [])
    {
        if (is_array($config) && !empty($config['url'])) {
            $config = array_merge($config, $this->parseDsn($config['url']));
        } elseif (is_string($config)) {
            $config = $this->parseDsn($config['url']);
        }

        $this->settings = array_merge($this->baseConfig, $config);
        return $this->connected = $this->connect();
    }

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
        return '\\josegonzalez\\Queuesadilla\\Job';
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

    public function watch($options = [])
    {
        $this->setting($options, 'queue');
        return true;
    }

    public function connected()
    {
        return $this->connected;
    }

    public function jobId()
    {
        return rand();
    }

    abstract public function connect();

    abstract public function delete($item);

    abstract public function pop($options = []);

    abstract public function push($class, $vars = [], $options = []);

    abstract public function release($item, $options = []);
}
