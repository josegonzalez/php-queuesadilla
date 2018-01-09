<?php

namespace josegonzalez\Queuesadilla\Utility;

use Pheanstalk\Command\DeleteCommand;
use Pheanstalk\Command\ReleaseCommand;
use Pheanstalk\PheanstalkInterface;
use ReflectionClass;

class Pheanstalk extends \Pheanstalk\Pheanstalk
{
    public function releaseJob(
        $job,
        $priority = PheanstalkInterface::DEFAULT_PRIORITY,
        $delay = PheanstalkInterface::DEFAULT_DELAY
    ) {
        return $this->protectedMethodCall($this, [new ReleaseCommand($job, $priority, $delay)]);
    }

    public function deleteJob($job)
    {
        return $this->protectedMethodCall($this, [new DeleteCommand($job)]);
    }

    public function protectedMethodCall($object, $parameters)
    {
        $reflection = new ReflectionClass(get_class($object));
        $method = $reflection->getMethod('_dispatch');
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
