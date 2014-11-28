<?php

namespace josegonzalez\Queuesadilla\Utility;

use \Pheanstalk\Command\DeleteCommand;
use \Pheanstalk\Command\ReleaseCommand;
use \Pheanstalk\PheanstalkInterface;

class Pheanstalk extends \Pheanstalk\Pheanstalk
{
    public function releaseJob(
        $job,
        $priority = PheanstalkInterface::DEFAULT_PRIORITY,
        $delay = PheanstalkInterface::DEFAULT_DELAY
    ) {
        return $this->dispatchCommand(new ReleaseCommand($job, $priority, $delay));
    }

    public function deleteJob($job)
    {
        return $this->dispatchCommand(new DeleteCommand($job));
    }

    public function dispatchCommand($command)
    {
        $connection = $this->getConnection();
        try {
            $response = $connection->dispatchCommand($command);
        } catch (Exception\SocketException $e) {
            $this->_reconnect();
            $response = $connection->dispatchCommand($command);
        }

        return $response;
    }
}
