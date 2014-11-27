<?php

namespace josegonzalez\Queuesadilla\Job;

use \josegonzalez\Queuesadilla\Job;

class BeanstalkJob extends Job
{
    public function attempts()
    {
        $stats = $this->engine->statsJob($this->item);
        if ($stats !== null) {
            return (int)$stats['reserves'];
        }

        return null;
    }
}
