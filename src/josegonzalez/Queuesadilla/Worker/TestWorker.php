<?php

namespace josegonzalez\Queuesadilla\Worker;

use \josegonzalez\Queuesadilla\Worker;

class TestWorker extends Worker
{
    public function work()
    {
        return $this->engine->pop();
    }
}
