<?php

namespace josegonzalez\Queuesadilla\Job;

use josegonzalez\Queuesadilla\Job\Base;

class BeanstalkJob extends Base
{
    public function attempts()
    {
        $stats = $this->engine->connection->statsJob($this->item['id']);
        if ($stats !== null) {
            return (int)$stats['reserves'];
        }

        return null;
    }
}
