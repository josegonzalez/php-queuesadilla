<?php

namespace josegonzalez\Queuesadilla;

use josegonzalez\Queuesadilla\Engine\NullEngine;
use josegonzalez\Queuesadilla\Job\Base as BaseJob;
use josegonzalez\Queuesadilla\TestCase;
use josegonzalez\Queuesadilla\Worker\SequentialWorker;
use Psr\Log\NullLogger;

function fail_method()
{
    return false;
}
function null_method()
{
    return true;
}
function true_method()
{
    return true;
}

class MyJob
{
    public function performTrue()
    {
        return true;
    }
    public function performFail()
    {
        return false;
    }
    public function performNull()
    {
        return null;
    }
    public function performException()
    {
        throw new \Exception("Exception");
    }
    public function perform($job)
    {
        return $job->data('return');
    }
    public function performStatic()
    {
        return true;
    }
}

class SequentialWorkerTest extends TestCase
{
    public function setUp() : void
    {
        $this->Engine = new NullEngine;
        $this->Worker = new SequentialWorker($this->Engine);
        $this->Item = [
            'class' => ['josegonzalez\Queuesadilla\MyJob', 'perform'],
            'args' => [['return' => true]],
        ];
        $this->ItemFail = [
            'class' => ['josegonzalez\Queuesadilla\MyJob', 'performFail'],
            'args' => [['return' => true]],
        ];
        $this->ItemException = [
            'class' => ['josegonzalez\Queuesadilla\MyJob', 'performException'],
            'args' => [['return' => true]],
        ];
        $this->Job = new BaseJob($this->Item, $this->Engine);
    }

    public function tearDown() : void
    {
        unset($this->Engine);
        unset($this->Worker);
    }

    /**
     * @covers josegonzalez\Queuesadilla\Worker\Base::__construct
     * @covers josegonzalez\Queuesadilla\Worker\SequentialWorker::__construct
     */
    public function testConstruct()
    {
        $Worker = new SequentialWorker($this->Engine);
        $this->assertInstanceOf('\josegonzalez\Queuesadilla\Worker\Base', $Worker);
        $this->assertInstanceOf('\josegonzalez\Queuesadilla\Worker\SequentialWorker', $Worker);
        $this->assertInstanceOf('\Psr\Log\LoggerInterface', $Worker->logger());
        $this->assertInstanceOf('\Psr\Log\NullLogger', $Worker->logger());
    }

    /**
     * @covers josegonzalez\Queuesadilla\Worker\SequentialWorker::work
     */
    public function testWork()
    {
        $Engine = new NullEngine;
        $Engine->return = false;
        $Worker = new SequentialWorker($Engine);
        $this->assertFalse($Worker->work());

        $Engine = $this->getMockBuilder('josegonzalez\Queuesadilla\Engine\NullEngine')
                ->setMethods(['pop'])
                ->getMock();
        $Engine->expects($this->at(0))
                ->method('pop')
                ->will($this->returnValue(true));
        $Engine->expects($this->at(1))
                ->method('pop')
                ->will($this->returnValue($this->Item));
        $Engine->expects($this->at(2))
                ->method('pop')
                ->will($this->returnValue($this->ItemFail));
        $Engine->expects($this->at(3))
                ->method('pop')
                ->will($this->returnValue($this->ItemException));
        $Engine->expects($this->at(4))
                ->method('pop')
                ->will($this->returnValue(false));
        $Worker = new SequentialWorker($Engine, null, ['maxIterations' => 5]);
        $this->assertTrue($Worker->work());
        $this->assertEquals([
            'seen' => 5,
            'empty' => 1,
            'exception' => 1,
            'invalid' => 1,
            'success' => 1,
            'failure' => 2,
            'connectionFailed' => 0,
            'maxIterations' => 1,
            'maxRuntime' => 0,
        ], $Worker->stats());
        $Worker = new SequentialWorker($Engine, null, ['maxRuntime' => 5]);
        $this->assertTrue($Worker->work());
        $this->assertEquals(1, $Worker->stats()['maxRuntime']);
    }

    /**
     * @covers josegonzalez\Queuesadilla\Worker\SequentialWorker::connect
     */
    public function testConnect()
    {
        $this->assertTrue($this->Worker->connect());

        $Engine = new NullEngine;
        $Engine->return = false;
        $Worker = new SequentialWorker($Engine);
        $this->assertFalse($Worker->connect());
    }

    /**
     * @covers josegonzalez\Queuesadilla\Worker\SequentialWorker::perform
     */
    public function testPerform()
    {
        $this->assertFalse($this->Worker->perform([
            'class' => 'josegonzalez\Queuesadilla\nonexistent_method'
        ], null));
        $this->assertFalse($this->Worker->perform([
            'class' => 'josegonzalez\Queuesadilla\fail_method'
        ], null));
        $this->assertTrue($this->Worker->perform([
            'class' => 'josegonzalez\Queuesadilla\null_method'
        ], null));
        $this->assertTrue($this->Worker->perform([
            'class' => 'josegonzalez\Queuesadilla\true_method'
        ], null));
        $this->assertFalse($this->Worker->perform([
            'class' => ['josegonzalez\Queuesadilla\MyJob', 'performFail']
        ], null));
        $this->assertTrue($this->Worker->perform([
            'class' => ['josegonzalez\Queuesadilla\MyJob', 'performTrue']
        ], null));
        $this->assertTrue($this->Worker->perform([
            'class' => ['josegonzalez\Queuesadilla\MyJob', 'performNull']
        ], null));
        $this->assertTrue($this->Worker->perform([
            'class' => ['josegonzalez\Queuesadilla\MyJob', 'perform']
        ], $this->Job));
    }

    /**
     * @covers josegonzalez\Queuesadilla\Worker\SequentialWorker::disconnect
     */
    public function testDisconnect()
    {
        $this->assertNull($this->protectedMethodCall($this->Worker, 'disconnect'));
    }

    /**
     * @covers josegonzalez\Queuesadilla\Worker\SequentialWorker::signalHandler
     */
    public function testSignalHandler()
    {
        $this->assertEquals(true, $this->Worker->signalHandler());
        $this->assertEquals(true, $this->Worker->signalHandler(SIGQUIT));
        $this->assertEquals(true, $this->Worker->signalHandler(SIGTERM));
        $this->assertEquals(true, $this->Worker->signalHandler(SIGINT));
        $this->assertEquals(true, $this->Worker->signalHandler(SIGUSR1));
    }

    /**
     * @covers josegonzalez\Queuesadilla\Worker\SequentialWorker::shutdownHandler
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
     * tests Worker.job.empty logs to debug
     *
     * @return void
     */
    public function testJobEmptyEvent()
    {
        $Logger = $this->getMockBuilder(NullLogger::class)
            ->setMethods(['debug'])
            ->getMock();
        $Logger
            ->expects($this->at(0))
            ->method('debug')
            ->with('No job!');

        $Engine = $this->getMockBuilder(NullEngine::class)
                ->setMethods(['pop'])
                ->getMock();
        $Engine->expects($this->at(0))
                ->method('pop')
                ->will($this->returnValue(false));

        $Worker = new SequentialWorker($Engine, $Logger, ['maxIterations' => 1]);
        $this->assertTrue($Worker->work());
    }

    /**
     * tests Worker.job.exception logs to alert
     *
     * @return void
     */
    public function testJobExceptionEvent()
    {
        $Logger = $this->getMockBuilder(NullLogger::class)
            ->setMethods(['alert'])
            ->getMock();
        $Logger
            ->expects($this->at(0))
            ->method('alert')
            ->with('Exception: "Exception"');

        $Engine = $this->getMockBuilder(NullEngine::class)
                ->setMethods(['pop'])
                ->getMock();
        $Engine->expects($this->at(0))
                ->method('pop')
                ->will($this->returnValue($this->ItemException));

        $Worker = new SequentialWorker($Engine, $Logger, ['maxIterations' => 1]);
        $this->assertTrue($Worker->work());
    }

    /**
     * tests Worker.job.success logs to debug
     *
     * @return void
     */
    public function testJobSuccessEvent()
    {
        $Logger = $this->getMockBuilder(NullLogger::class)
            ->setMethods(['debug'])
            ->getMock();
        $Logger
            ->expects($this->at(0))
            ->method('debug')
            ->with('Success. Acknowledging job on queue.');

        $Engine = $this->getMockBuilder(NullEngine::class)
                ->setMethods(['pop'])
                ->getMock();
        $Engine->expects($this->at(0))
                ->method('pop')
                ->will($this->returnValue($this->Item));

        $Worker = new SequentialWorker($Engine, $Logger, ['maxIterations' => 1]);
        $this->assertTrue($Worker->work());
    }
}
