<?php

namespace josegonzalez\Queuesadilla\Engine;

use \josegonzalez\Queuesadilla\Engine\EngineInterface;
use \josegonzalez\Queuesadilla\Job;
use \josegonzalez\Queuesadilla\Utility\DsnParserTrait;

abstract class Base implements EngineInterface
{

    use DsnParserTrait;

    protected $baseConfig = array(array(
        'queue' => 'default',
    ));

    protected $connected = false;

    protected $settings = array();

    public $connection = null;

    public function __construct($config = array())
    {
        if (is_array($config) && isset($config['dsn'])) {
            $config = array_merge($config, $this->parseDsn($config['dsn']));
        } elseif (is_string($config)) {
            $config = $this->parseDsn($config['dsn']);
        }

        $this->settings = array_merge($this->baseConfig, $config);
        return $this->connected = $this->connect();
    }

    public function bulk($jobs, $vars = array(), $options = array())
    {
        $queue = $this->setting($options, 'queue');
        $return = array();
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
            $settings = array('queue' => $settings);
        }

        $settings = array_merge($this->settings, $settings);

        if (isset($settings[$key])) {
            $value = $settings[$key];
        } else {
            $value = $default;
        }

        return $value;
    }

    public function watch($options = array())
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

    abstract public function pop($options = array());

    abstract public function push($class, $vars = array(), $options = array());

    abstract public function release($item, $options = array());
}
