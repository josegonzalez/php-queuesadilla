<?php

namespace josegonzalez\Queuesadilla\Worker\Listener;

use josegonzalez\Queuesadilla\TestCase;
use josegonzalez\Queuesadilla\Worker\Listener\StatsListener;

class StatsListenerTest extends TestCase
{
    public function setUp() : void
    {
        $this->StatsListener = new StatsListener;
    }

    public function tearDown() : void
    {
        unset($this->StatsListener);
    }

    /**
     * @covers josegonzalez\Queuesadilla\Worker\Listener\StatsListener::__construct
     */
    public function testConstruct()
    {
        $StatsListener = new StatsListener;
        $this->assertInstanceOf('\josegonzalez\Queuesadilla\Worker\Listener\StatsListener', $StatsListener);
    }

    /**
     * @covers josegonzalez\Queuesadilla\Worker\Listener\StatsListener::implementedEvents
     */
    public function testImplementedEvents()
    {
        $this->assertEquals([
            'Worker.connectionFailed' => 'connectionFailed',
            'Worker.maxIterations' => 'maxIterations',
            'Worker.maxRuntime' => 'maxRuntime',
            'Worker.job.seen' => 'jobSeen',
            'Worker.job.empty' => 'jobEmpty',
            'Worker.job.invalid' => 'jobInvalid',
            'Worker.job.start' => 'jobStart',
            'Worker.job.exception' => 'jobException',
            'Worker.job.success' => 'jobSuccess',
            'Worker.job.failure' => 'jobFailure',
        ], $this->StatsListener->implementedEvents());
    }

    /**
     * @covers josegonzalez\Queuesadilla\Worker\Listener\StatsListener::connectionFailed
     */
    public function testConnectionFailed()
    {
        $this->assertEquals([
            'connectionFailed' => 0,
            'maxIterations' => 0,
            'maxRuntime' => 0,
            'seen' => 0,
            'empty' => 0,
            'exception' => 0,
            'invalid' => 0,
            'success' => 0,
            'failure' => 0,
        ], $this->StatsListener->stats);

        $this->StatsListener->connectionFailed();

        $this->assertEquals([
            'connectionFailed' => 1,
            'maxIterations' => 0,
            'maxRuntime' => 0,
            'seen' => 0,
            'empty' => 0,
            'exception' => 0,
            'invalid' => 0,
            'success' => 0,
            'failure' => 0,
        ], $this->StatsListener->stats);
    }

    /**
     * @covers josegonzalez\Queuesadilla\Worker\Listener\StatsListener::maxIterations
     */
    public function testMaxIterations()
    {
        $this->assertEquals([
            'connectionFailed' => 0,
            'maxIterations' => 0,
            'maxRuntime' => 0,
            'seen' => 0,
            'empty' => 0,
            'exception' => 0,
            'invalid' => 0,
            'success' => 0,
            'failure' => 0,
        ], $this->StatsListener->stats);

        $this->StatsListener->maxIterations();

        $this->assertEquals([
            'connectionFailed' => 0,
            'maxIterations' => 1,
            'maxRuntime' => 0,
            'seen' => 0,
            'empty' => 0,
            'exception' => 0,
            'invalid' => 0,
            'success' => 0,
            'failure' => 0,
        ], $this->StatsListener->stats);
    }

    /**
     * @covers josegonzalez\Queuesadilla\Worker\Listener\StatsListener::maxRuntime
     */
    public function testMaxRuntime()
    {
        $this->assertEquals([
            'connectionFailed' => 0,
            'maxIterations' => 0,
            'maxRuntime' => 0,
            'seen' => 0,
            'empty' => 0,
            'exception' => 0,
            'invalid' => 0,
            'success' => 0,
            'failure' => 0,
        ], $this->StatsListener->stats);

        $this->StatsListener->maxRuntime();

        $this->assertEquals([
            'connectionFailed' => 0,
            'maxIterations' => 0,
            'maxRuntime' => 1,
            'seen' => 0,
            'empty' => 0,
            'exception' => 0,
            'invalid' => 0,
            'success' => 0,
            'failure' => 0,
        ], $this->StatsListener->stats);
    }

    /**
     * @covers josegonzalez\Queuesadilla\Worker\Listener\StatsListener::jobSeen
     */
    public function testJobSeen()
    {
        $this->assertEquals([
            'connectionFailed' => 0,
            'maxIterations' => 0,
            'maxRuntime' => 0,
            'seen' => 0,
            'empty' => 0,
            'exception' => 0,
            'invalid' => 0,
            'success' => 0,
            'failure' => 0,
        ], $this->StatsListener->stats);

        $this->StatsListener->jobSeen();

        $this->assertEquals([
            'connectionFailed' => 0,
            'maxIterations' => 0,
            'maxRuntime' => 0,
            'seen' => 1,
            'empty' => 0,
            'exception' => 0,
            'invalid' => 0,
            'success' => 0,
            'failure' => 0,
        ], $this->StatsListener->stats);
    }

    /**
     * @covers josegonzalez\Queuesadilla\Worker\Listener\StatsListener::jobEmpty
     */
    public function testJobEmpty()
    {
        $this->assertEquals([
            'connectionFailed' => 0,
            'maxIterations' => 0,
            'maxRuntime' => 0,
            'seen' => 0,
            'empty' => 0,
            'exception' => 0,
            'invalid' => 0,
            'success' => 0,
            'failure' => 0,
        ], $this->StatsListener->stats);

        $this->StatsListener->jobEmpty();

        $this->assertEquals([
            'connectionFailed' => 0,
            'maxIterations' => 0,
            'maxRuntime' => 0,
            'seen' => 0,
            'empty' => 1,
            'exception' => 0,
            'invalid' => 0,
            'success' => 0,
            'failure' => 0,
        ], $this->StatsListener->stats);
    }

    /**
     * @covers josegonzalez\Queuesadilla\Worker\Listener\StatsListener::jobException
     */
    public function testJobException()
    {
        $this->assertEquals([
            'connectionFailed' => 0,
            'maxIterations' => 0,
            'maxRuntime' => 0,
            'seen' => 0,
            'empty' => 0,
            'exception' => 0,
            'invalid' => 0,
            'success' => 0,
            'failure' => 0,
        ], $this->StatsListener->stats);

        $this->StatsListener->jobException();

        $this->assertEquals([
            'connectionFailed' => 0,
            'maxIterations' => 0,
            'maxRuntime' => 0,
            'seen' => 0,
            'empty' => 0,
            'exception' => 1,
            'invalid' => 0,
            'success' => 0,
            'failure' => 0,
        ], $this->StatsListener->stats);
    }

    /**
     * @covers josegonzalez\Queuesadilla\Worker\Listener\StatsListener::jobInvalid
     */
    public function testJobInvalid()
    {
        $this->assertEquals([
            'connectionFailed' => 0,
            'maxIterations' => 0,
            'maxRuntime' => 0,
            'seen' => 0,
            'empty' => 0,
            'exception' => 0,
            'invalid' => 0,
            'success' => 0,
            'failure' => 0,
        ], $this->StatsListener->stats);

        $this->StatsListener->jobInvalid();

        $this->assertEquals([
            'connectionFailed' => 0,
            'maxIterations' => 0,
            'maxRuntime' => 0,
            'seen' => 0,
            'empty' => 0,
            'exception' => 0,
            'invalid' => 1,
            'success' => 0,
            'failure' => 0,
        ], $this->StatsListener->stats);
    }

    /**
     * @covers josegonzalez\Queuesadilla\Worker\Listener\StatsListener::jobSuccess
     */
    public function testJobSuccess()
    {
        $this->assertEquals([
            'connectionFailed' => 0,
            'maxIterations' => 0,
            'maxRuntime' => 0,
            'seen' => 0,
            'empty' => 0,
            'exception' => 0,
            'invalid' => 0,
            'success' => 0,
            'failure' => 0,
        ], $this->StatsListener->stats);

        $this->StatsListener->jobSuccess();

        $this->assertEquals([
            'connectionFailed' => 0,
            'maxIterations' => 0,
            'maxRuntime' => 0,
            'seen' => 0,
            'empty' => 0,
            'exception' => 0,
            'invalid' => 0,
            'success' => 1,
            'failure' => 0,
        ], $this->StatsListener->stats);
    }

    /**
     * @covers josegonzalez\Queuesadilla\Worker\Listener\StatsListener::jobFailure
     */
    public function testJobFailure()
    {
        $this->assertEquals([
            'connectionFailed' => 0,
            'maxIterations' => 0,
            'maxRuntime' => 0,
            'seen' => 0,
            'empty' => 0,
            'exception' => 0,
            'invalid' => 0,
            'success' => 0,
            'failure' => 0,
        ], $this->StatsListener->stats);

        $this->StatsListener->jobFailure();

        $this->assertEquals([
            'connectionFailed' => 0,
            'maxIterations' => 0,
            'maxRuntime' => 0,
            'seen' => 0,
            'empty' => 0,
            'exception' => 0,
            'invalid' => 0,
            'success' => 0,
            'failure' => 1,
        ], $this->StatsListener->stats);
    }
}
