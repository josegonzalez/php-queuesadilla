<?php

namespace josegonzalez\Queuesadilla\Utility;

use Pheanstalk\Command\DeleteCommand;
use Pheanstalk\Command\ReleaseCommand;
use Pheanstalk\PheanstalkInterface;

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
            $this->reconnect();
            $response = $connection->dispatchCommand($command);
        }

        return $response;
    }

    public function reconnect()
    {
        $new_connection = new Connection(
            $this->_connection->getHost(),
            $this->_connection->getPort(),
            $this->_connection->getConnectTimeout()
        );

        $this->setConnection($new_connection);

        if ($this->_using != PheanstalkInterface::DEFAULT_TUBE) {
            $tube = $this->_using;
            $this->_using = null;
            $this->useTube($tube);
        }

        foreach ($this->_watching as $tube => $true) {
            $true;
            if ($tube != PheanstalkInterface::DEFAULT_TUBE) {
                unset($this->_watching[$tube]);
                $this->watch($tube);
            }
        }

        if (!isset($this->_watching[PheanstalkInterface::DEFAULT_TUBE])) {
            $this->ignore(PheanstalkInterface::DEFAULT_TUBE);
        }
    }
}
