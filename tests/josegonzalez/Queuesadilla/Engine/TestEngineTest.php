<?php

namespace josegonzalez\Queuesadilla\Engine;

use \josegonzalez\Queuesadilla\Engine\TestEngine;
use \PHPUnit_Framework_TestCase;
use \Psr\Log\NullLogger;

class TestEngineTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->Logger = new NullLogger;
        $this->Engine = new TestEngine($this->Logger, [
            'queue' => 'default',
        ]);
    }

    public function tearDown()
    {
        unset($this->Engine);
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\Base::__construct
     * @covers josegonzalez\Queuesadilla\Engine\Base::connected
     */
    public function testConstruct()
    {
        $Engine = new TestEngine($this->Logger, []);
        $this->assertTrue($Engine->connected());

        $Engine = new TestEngine($this->Logger, 'test://user:pass@host:port');
        $this->assertTrue($Engine->connected());
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\Base::bulk
     */
    public function testBulk()
    {
        $this->assertEquals([true, true], $this->Engine->bulk([null, null]));

        $this->Engine->return = false;
        $this->assertEquals([false, false], $this->Engine->bulk([null, null]));
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\Base::getJobClass
     */
    public function testGetJobClass()
    {
        $this->assertEquals('\\josegonzalez\\Queuesadilla\\Job', $this->Engine->getJobClass());
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\Base::config
     */
    public function testConfig()
    {
        $this->assertEquals(['queue' => 'default'], $this->Engine->config());
        $this->assertEquals(['queue' => 'other'], $this->Engine->config(['queue' => 'other']));
        $this->assertEquals('other', $this->Engine->config('queue'));
        $this->assertEquals('another', $this->Engine->config('queue', 'another'));
        $this->assertEquals(null, $this->Engine->config('random'));
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\Base::setting
     */
    public function testSetting()
    {
        $this->assertEquals('string_to_array', $this->Engine->setting('string_to_array', 'queue'));
        $this->assertEquals('non_default', $this->Engine->setting(['queue' => 'non_default'], 'queue'));
        $this->assertEquals('default', $this->Engine->setting([], 'queue'));
        $this->assertEquals('other', $this->Engine->setting([], 'other', 'other'));
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\Base::connect
     * @covers josegonzalez\Queuesadilla\Engine\TestEngine::connect
     */
    public function testConnect()
    {
        $this->assertTrue($this->Engine->connect());

        $this->Engine->return = false;
        $this->assertFalse($this->Engine->connect());
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\Base::delete
     * @covers josegonzalez\Queuesadilla\Engine\TestEngine::delete
     */
    public function testDelete()
    {
        $this->assertTrue($this->Engine->delete(null));

        $this->Engine->return = false;
        $this->assertFalse($this->Engine->delete(null));
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\Base::pop
     * @covers josegonzalez\Queuesadilla\Engine\TestEngine::pop
     */
    public function testPop()
    {
        $this->assertTrue($this->Engine->pop('default'));

        $this->Engine->return = false;
        $this->assertFalse($this->Engine->pop('default'));
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\Base::push
     * @covers josegonzalez\Queuesadilla\Engine\TestEngine::push
     */
    public function testPush()
    {
        $this->assertTrue($this->Engine->push(null, [], 'default'));

        $this->Engine->return = false;
        $this->assertFalse($this->Engine->connect(null, [], 'default'));
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\Base::release
     * @covers josegonzalez\Queuesadilla\Engine\TestEngine::release
     */
    public function testRelease()
    {
        $this->assertTrue($this->Engine->release(null, 'default'));

        $this->Engine->return = false;
        $this->assertFalse($this->Engine->release(null, 'default'));
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\Base::jobId
     */
    public function testJobId()
    {
        $this->assertInternalType('int', $this->Engine->jobId());
        $this->assertInternalType('int', $this->Engine->jobId());
        $this->assertInternalType('int', $this->Engine->jobId());
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\Base::queues
     * @covers josegonzalez\Queuesadilla\Engine\TestEngine::queues
     */
    public function testQueues()
    {
        $this->assertEquals([], $this->Engine->queues());
    }
}
