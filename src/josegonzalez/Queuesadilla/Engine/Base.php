<?php

namespace josegonzalez\Queuesadilla\Engine;

use josegonzalez\Queuesadilla\Engine\EngineInterface;
use josegonzalez\Queuesadilla\Job;
use josegonzalez\Queuesadilla\Utility\DsnParserTrait;
use josegonzalez\Queuesadilla\Utility\LoggerTrait;
use josegonzalez\Queuesadilla\Utility\SettingTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

abstract class Base implements EngineInterface
{

    use DsnParserTrait;

    use LoggerTrait;

    use SettingTrait;

    protected $baseConfig = [];

    protected $connection = null;

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
        $this->config($this->baseConfig);
        $this->config($config);

        return $this;
    }

    public function getJobClass()
    {
        return '\\josegonzalez\\Queuesadilla\\Job\\Base';
    }

    public function connection()
    {
        if ($this->connection === null) {
            $this->connect();
        }

        return $this->connection;
    }

    public function lastJobId()
    {
        return $this->lastJobId;
    }

    public function acknowledge($item)
    {
        if (!is_array($item)) {
            return false;
        }

        return !empty($item['id']) && !empty($item['queue']);
    }
}
