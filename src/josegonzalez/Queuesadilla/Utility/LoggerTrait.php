<?php

namespace josegonzalez\Queuesadilla\Utility;

use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

trait LoggerTrait
{
    protected $logger = null;

    public function setLogger(LoggerInterface $logger = null)
    {
        if ($logger === null) {
            $logger = new NullLogger;
        }

        return $this->logger = $logger;
    }

    public function logger()
    {
        return $this->logger;
    }
}
