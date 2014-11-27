<?php

namespace josegonzalez\Queuesadilla\Engine;

use \josegonzalez\Queuesadilla\Engine\MemoryEngine;
use \PHPUnit_Framework_TestCase;

class MemoryEngineTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->Engine = new MemoryEngine();
    }

    public function tearDown()
    {
        unset($this->Engine);
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\MemoryEngine::__construct
     * @covers josegonzalez\Queuesadilla\Engine\MemoryEngine::connected
     */
    public function testConstruct()
    {
        $Engine = new MemoryEngine();
        $this->assertTrue($Engine->connected());
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\MemoryEngine::connect
     */
    public function testConnect()
    {
        $this->assertTrue($this->Engine->connect());
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\MemoryEngine::delete
     */
    public function testDelete()
    {
        $engineClass = 'josegonzalez\Queuesadilla\Engine\MemoryEngine';
        $Engine = $this->getMock($engineClass, ['jobId']);
        $Engine->expects($this->any())
                ->method('jobId')
                ->will($this->returnValue('1'));

        $this->assertFalse($Engine->delete(null));
        $this->assertFalse($Engine->delete(false));
        $this->assertFalse($Engine->delete(1));
        $this->assertFalse($Engine->delete('string'));
        $this->assertFalse($Engine->delete(['key' => 'value']));
        $this->assertFalse($Engine->delete(['id' => '1']));

        $this->assertTrue($Engine->push('some_function'));
        $this->assertTrue($Engine->delete(['id' => '1']));
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\MemoryEngine::pop
     */
    public function testPop()
    {
        $engineClass = 'josegonzalez\Queuesadilla\Engine\MemoryEngine';
        $Engine = $this->getMock($engineClass, ['jobId']);
        $Engine->expects($this->any())
                ->method('jobId')
                ->will($this->returnValue('1'));

        $this->assertNull($Engine->pop('default'));
        $this->assertTrue($Engine->push(null, [], 'default'));
        $this->assertEquals([
            'id' => '1',
            'class' => null,
            'vars' => [],
            'options' => ['queue' => 'default'],
        ], $Engine->pop('default'));
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\MemoryEngine::push
     * @covers josegonzalez\Queuesadilla\Engine\MemoryEngine::pop
     */
    public function testPush()
    {
        $this->assertTrue($this->Engine->push(null, [], 'default'));
        $this->assertTrue($this->Engine->push('some_function', [], [
            'delay' => 30,
        ]));
        $this->assertTrue($this->Engine->push('another_function', [], [
            'expires_in' => 1,
        ]));
        $this->assertTrue($this->Engine->push('yet_another_function', [], 'default'));

        sleep(2);

        $pop1 = $this->Engine->pop();
        $pop2 = $this->Engine->pop();
        $pop3 = $this->Engine->pop();
        $pop4 = $this->Engine->pop();

        $this->assertNotEmpty($pop1['id']);
        $this->assertNull($pop1['class']);
        $this->assertEmpty($pop1['vars']);
        $this->assertEquals('yet_another_function', $pop2['class']);
        $this->assertNull($pop3);
        $this->assertNull($pop4);
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\MemoryEngine::release
     */
    public function testRelease()
    {
        $this->assertFalse($this->Engine->release(null, 'default'));
    }
}
