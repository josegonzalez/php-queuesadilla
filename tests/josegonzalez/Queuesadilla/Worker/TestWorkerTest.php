<?php

namespace josegonzalez\Queuesadilla;

use josegonzalez\Queuesadilla\Engine\NullEngine;
use josegonzalez\Queuesadilla\TestCase;
use josegonzalez\Queuesadilla\Worker\TestWorker;
use Psr\Log\LoggerInterface;

class TestWorkerTest extends TestCase
{
    public function setUp() : void
    {
        $this->Engine = new NullEngine;
        $this->Worker = new TestWorker($this->Engine);
    }

    public function tearDown() : void
    {
        unset($this->Engine);
        unset($this->Worker);
    }

    /**
     * @covers josegonzalez\Queuesadilla\Worker\Base::__construct
     * @covers josegonzalez\Queuesadilla\Worker\TestWorker::__construct
     */
    public function testConstruct()
    {
        $this->assertInstanceOf('\josegonzalez\Queuesadilla\Worker\Base', $this->Worker);
        $this->assertInstanceOf('\Psr\Log\LoggerInterface', $this->Worker->logger());
        $this->assertInstanceOf('\Psr\Log\NullLogger', $this->Worker->logger());
    }

    /**
     * @covers josegonzalez\Queuesadilla\Worker\Base::work
     * @covers josegonzalez\Queuesadilla\Worker\TestWorker::work
     */
    public function testWork()
    {
        $this->assertTrue($this->Worker->work());
        $this->assertTrue($this->Worker->work());
        $this->assertTrue($this->Worker->work());
    }

    /**
     * @covers josegonzalez\Queuesadilla\Worker\Base::stats
     */
    public function testStats()
    {
        $this->assertEquals([
            'seen' => 0,
            'empty' => 0,
            'exception' => 0,
            'invalid' => 0,
            'success' => 0,
            'failure' => 0,
            'connectionFailed' => 0,
            'maxIterations' => 0,
            'maxRuntime' => 0,
        ], $this->Worker->stats());
    }

    /**
     * @covers josegonzalez\Queuesadilla\Worker\Base::shutdownHandler
     */
    public function testShutdownHandler()
    {
        $this->assertEquals(true, $this->Worker->shutdownHandler());
        $this->assertEquals(true, $this->Worker->shutdownHandler(SIGQUIT));
        $this->assertEquals(true, $this->Worker->shutdownHandler(SIGTERM));
        $this->assertEquals(true, $this->Worker->shutdownHandler(SIGINT));
        $this->assertEquals(true, $this->Worker->shutdownHandler(SIGUSR1));
    }

    /**
     * @covers josegonzalez\Queuesadilla\Worker\Base::disconnect
     * @covers josegonzalez\Queuesadilla\Worker\TestWorker::disconnect
     */
    public function testDisconnect()
    {
        $this->assertTrue($this->protectedMethodCall($this->Worker, 'disconnect'));
    }
}
