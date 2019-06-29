<?php

namespace josegonzalez\Queuesadilla;

use josegonzalez\Queuesadilla\Engine\NullEngine;
use josegonzalez\Queuesadilla\Queue;
use josegonzalez\Queuesadilla\TestCase;

class QueueTest extends TestCase
{
    public function setUp() : void
    {
        $this->Engine = new NullEngine;
        $this->Queue = new Queue($this->Engine);
    }

    /**
     * @covers josegonzalez\Queuesadilla\Queue::__construct
     */
    public function testConstruct()
    {
        $Queue = new Queue($this->Engine);
        $this->assertInstanceOf('\josegonzalez\Queuesadilla\Queue', $Queue);
    }
    /**
     * @covers josegonzalez\Queuesadilla\Queue::push
     */
    public function testPush()
    {
        $this->assertTrue($this->Queue->push([]));
    }
}
