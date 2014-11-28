<?php

namespace josegonzalez\Queuesadilla\Worker;

use josegonzalez\Queuesadilla\Worker\Base;

class TestWorker extends Base
{
    public function work()
    {
        return $this->engine->pop();
    }
}
