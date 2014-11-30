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

    protected $baseConfig = [];

    protected $connected = null;

    protected $connection = null;

    protected $settings = [];

    public $lastJobId = null;

    public function __construct(LoggerInterface $logger = null, $config = [])
    {
        if (is_array($config) && !empty($config['url'])) {
            $url = $config['url'];
            unset($config['url']);
            $config = array_merge($config, $this->parseDsn($url));
        } elseif (is_string($config)) {
            $config = $this->parseDsn($config);
        }

        $this->setLogger($logger);
        $this->settings = $this->baseConfig;
        $this->config($config);
        return $this;
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
            return $settings[$key];
        }
        return $default;
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

    public function createJobId()
    {
        return rand();
    }

    public function lastJobId()
    {
        return $this->lastJobId;
    }

    public function delete($item)
    {
        if (!is_array($item)) {
            return false;
        }
        return !empty($item['id']) && !empty($item['queue']);
    }
}
