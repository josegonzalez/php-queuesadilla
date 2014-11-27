<?php

namespace josegonzalez\Queuesadilla\Engine;

use \josegonzalez\Queuesadilla\Engine\RedisEngine;
use \PHPUnit_Framework_TestCase;

class RedisEngineTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        if (!class_exists('Redis')) {
            $this->markTestSkipped('Redis is not installed or configured properly.');
        }

        $this->config = array(
            'queue' => 'default',
            'user' => 'travis',
            'pass' => '',
        );
        $this->Engine = $this->getMock('josegonzalez\Queuesadilla\Engine\RedisEngine', array('jobId'), array($this->config));
        $this->Engine->expects($this->any())
                ->method('jobId')
                ->will($this->returnValue('1'));

        $this->Engine->connection->flushdb();
    }

    public function tearDown()
    {
        $this->Engine->connection->flushdb();
        unset($this->Engine);
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\RedisEngine::__construct
     * @covers josegonzalez\Queuesadilla\Engine\RedisEngine::connected
     */
    public function testConstruct()
    {
        $Engine = new RedisEngine($this->config);
        $this->assertTrue($Engine->connected());
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\RedisEngine::connect
     */
    public function testConnect()
    {
        $this->assertTrue($this->Engine->connect());
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\RedisEngine::delete
     */
    public function testDelete()
    {
        $this->assertFalse($this->Engine->delete(null));
        $this->assertFalse($this->Engine->delete(false));
        $this->assertFalse($this->Engine->delete(1));
        $this->assertFalse($this->Engine->delete('string'));
        $this->assertFalse($this->Engine->delete(array('key' => 'value')));
        $this->assertTrue($this->Engine->delete(array('id' => '1')));

        $this->assertEquals(1, $this->Engine->push('some_function'));
        $this->assertTrue($this->Engine->delete(array('id' => '1')));
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\RedisEngine::pop
     */
    public function testPop()
    {
        $this->assertNull($this->Engine->pop('default'));
        $this->assertEquals(1, $this->Engine->push(null, [], 'default'));
        $this->assertEquals(array(
            'id' => '1',
            'class' => null,
            'vars' => []
        ), $this->Engine->pop('default'));
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\RedisEngine::push
     */
    public function testPush()
    {
        $this->assertEquals(1, $this->Engine->push(null, [], 'default'));
        $this->assertEquals(2, $this->Engine->push('1', [], 'default'));
        $this->assertEquals(3, $this->Engine->push('2', [], 'default'));
        $this->assertEquals(4, $this->Engine->push('3', [], 'default'));

        $pop1 = $this->Engine->pop();
        $pop2 = $this->Engine->pop();
        $pop3 = $this->Engine->pop();
        $pop4 = $this->Engine->pop();

        $this->assertNull($pop1['class']);
        $this->assertEquals('1', $pop2['class']);
        $this->assertEquals('2', $pop3['class']);
        $this->assertEquals('3', $pop4['class']);
    }
    /**
     * @covers josegonzalez\Queuesadilla\Engine\RedisEngine::push
     */
    public function testPushWithOptions()
    {
        $this->assertEquals(1, $this->Engine->push(null, [], 'default'));
        $this->assertEquals(2, $this->Engine->push('some_function', [], array(
            'delay' => 30,
        )));
        $this->assertEquals(3, $this->Engine->push('another_function', [], array(
            'expires_in' => 1,
        )));
        $this->assertEquals(4, $this->Engine->push('yet_another_function', [], 'default'));

        sleep(2);

        $pop1 = $this->Engine->pop();
        $pop2 = $this->Engine->pop();
        $pop3 = $this->Engine->pop();
        $pop4 = $this->Engine->pop();

        $this->assertNull($pop1['class']);
        $this->assertEmpty($pop1['vars']);

        $this->markTestIncomplete(
            'RedisEngine does not yet implement delay or expires_in (tbd sorted sets)'
        );

        $this->assertEquals('yet_another_function', $pop2['class']);
        $this->assertNull($pop3);
        $this->assertNull($pop4);
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\RedisEngine::pop
     * @covers josegonzalez\Queuesadilla\Engine\RedisEngine::release
     */
    public function testRelease()
    {
        $this->assertEquals(1, $this->Engine->push(null, [], 'default'));
        $this->assertEquals(array(
            'id' => '1',
            'class' => null,
            'vars' => []
        ), $this->Engine->pop('default'));

        $this->assertFalse($this->Engine->release(null, 'default'));

        $this->assertEquals(1, $this->Engine->release(array(
            'id' => '2',
            'class' => 'some_function',
            'vars' => []
        ), 'default'));
        $this->assertEquals(array(
            'id' => '2',
            'class' => 'some_function',
            'vars' => []
        ), $this->Engine->pop('default'));
    }
}
