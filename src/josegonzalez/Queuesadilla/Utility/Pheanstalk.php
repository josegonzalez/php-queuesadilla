<?php

namespace josegonzalez\Queuesadilla\Utility;

use \Pheanstalk\Command\DeleteCommand;

class Pheanstalk extends \Pheanstalk\Pheanstalk
{
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
