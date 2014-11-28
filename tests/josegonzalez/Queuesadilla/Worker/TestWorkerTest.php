<?php

namespace josegonzalez\Queuesadilla;

use josegonzalez\Queuesadilla\Engine\NullEngine;
use josegonzalez\Queuesadilla\Worker\TestWorker;
use josegonzalez\Queuesadilla\TestCase;
use Psr\Log\LoggerInterface;

class TestWorkerTest extends TestCase
{
    public function setUp()
    {
        $this->Engine = new NullEngine;
        $this->Worker = new TestWorker($this->Engine);
    }

    /**
     * @covers josegonzalez\Queuesadilla\Worker\Base::__construct
     * @covers josegonzalez\Queuesadilla\Worker\TestWorker::__construct
     */
    public function testConstruct()
    {
        $Worker = new TestWorker($this->Engine);
        $this->assertInstanceOf('\josegonzalez\Queuesadilla\Worker\Base', $Worker);
        $this->assertInstanceOf('\Psr\Log\LoggerInterface', $Worker->logger());
        $this->assertInstanceOf('\Psr\Log\NullLogger', $Worker->logger());
    }


    /**
     * @covers josegonzalez\Queuesadilla\Worker\Base::work
     * @covers josegonzalez\Queuesadilla\Worker\TestWorker::work
     */
    public function testWork()
    {
        $Worker = new TestWorker($this->Engine);
        $this->assertTrue($Worker->work());
        $this->assertTrue($Worker->work());
        $this->assertTrue($Worker->work());
    }
}
