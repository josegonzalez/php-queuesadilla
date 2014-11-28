<?php

namespace josegonzalez\Queuesadilla;

use \PHPUnit_Framework_TestCase;
use \josegonzalez\Queuesadilla\Engine\TestEngine;
use \josegonzalez\Queuesadilla\Queue;

class QueueTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->Engine = new TestEngine;
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
     * @covers josegonzalez\Queuesadilla\Queue::bulk
     */
    public function testBulk()
    {
        $this->assertEquals([], $this->Queue->bulk([]));
    }

    /**
     * @covers josegonzalez\Queuesadilla\Queue::push
     */
    public function testPush()
    {
        $this->assertTrue($this->Queue->push([]));
    }
}
