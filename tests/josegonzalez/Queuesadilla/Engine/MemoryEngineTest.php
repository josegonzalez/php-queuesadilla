<?php

namespace josegonzalez\Queuesadilla\Engine;

use josegonzalez\Queuesadilla\Engine\MemoryEngine;
use PHPUnit_Framework_TestCase;
use Psr\Log\NullLogger;
use ReflectionClass;

class MemoryEngineTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->url = getenv('MEMORY_URL');
        $this->config = ['url' => $this->url];
        $this->Logger = new NullLogger;
        $this->Engine = new MemoryEngine($this->Logger, $this->config);
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
        $Engine = new MemoryEngine($this->Logger, []);
        $this->assertTrue($Engine->connected());

        $Engine = new MemoryEngine($this->Logger, $this->url);
        $this->assertTrue($Engine->connected());

        $Engine = new MemoryEngine($this->Logger, $this->config);
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
     * @covers josegonzalez\Queuesadilla\Engine\Base::getJobClass
     */
    public function testGetJobClass()
    {
        $this->assertEquals('\\josegonzalez\\Queuesadilla\\Job\\Base', $this->Engine->getJobClass());
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\MemoryEngine::delete
     */
    public function testDelete()
    {
        $engineClass = 'josegonzalez\Queuesadilla\Engine\MemoryEngine';
        $Engine = $this->getMock($engineClass, ['createJobId'], [$this->Logger, $this->config]);
        $Engine->expects($this->at(0))
                ->method('createJobId')
                ->will($this->returnValue(1));
        $Engine->expects($this->at(1))
                ->method('createJobId')
                ->will($this->returnValue(2));

        $this->assertFalse($Engine->delete(null));
        $this->assertFalse($Engine->delete(false));
        $this->assertFalse($Engine->delete(1));
        $this->assertFalse($Engine->delete('string'));
        $this->assertFalse($Engine->delete(['key' => 'value']));
        $this->assertFalse($Engine->delete(['id' => 1, 'queue' => 'default']));

        $this->assertTrue($Engine->push('some_function'));
        $this->assertTrue($Engine->push('another_function', [], ['queue' => 'other']));
        $this->assertTrue($Engine->delete(['id' => 1, 'queue' => 'default']));
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\MemoryEngine::createJobId
     */
    public function testJobId()
    {
        $this->assertInternalType('int', $this->protectedMethodCall($this->Engine, 'createJobId'));
        $this->assertInternalType('int', $this->protectedMethodCall($this->Engine, 'createJobId'));
        $this->assertInternalType('int', $this->protectedMethodCall($this->Engine, 'createJobId'));
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\MemoryEngine::pop
     */
    public function testPop()
    {
        $engineClass = 'josegonzalez\Queuesadilla\Engine\MemoryEngine';
        $Engine = $this->getMock($engineClass, ['createJobId']);
        $Engine->expects($this->any())
                ->method('createJobId')
                ->will($this->returnValue('1'));

        $this->assertNull($Engine->pop('default'));
        $this->assertTrue($Engine->push(null, [], 'default'));
        $this->assertEquals([
            'id' => '1',
            'class' => null,
            'vars' => [],
            'options' => [],
            'queue' => 'default',
        ], $Engine->pop('default'));
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\MemoryEngine::push
     * @covers josegonzalez\Queuesadilla\Engine\MemoryEngine::pop
     * @covers josegonzalez\Queuesadilla\Engine\MemoryEngine::shouldDelay
     * @covers josegonzalez\Queuesadilla\Engine\MemoryEngine::shouldExpire
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

    /**
     * @covers josegonzalez\Queuesadilla\Engine\MemoryEngine::queues
     * @covers josegonzalez\Queuesadilla\Engine\MemoryEngine::requireQueue
     */
    public function testQueues()
    {
        $this->assertEquals([], $this->Engine->queues());
        $this->Engine->push('some_function');
        $this->assertEquals(['default'], $this->Engine->queues());

        $this->Engine->push('some_function', [], ['queue' => 'other']);
        $queues = $this->Engine->queues();
        sort($queues);
        $this->assertEquals(['default', 'other'], $queues);

        $this->Engine->pop();
        $this->Engine->pop();
        $queues = $this->Engine->queues();
        sort($queues);
        $this->assertEquals(['default', 'other'], $queues);
    }

    protected function protectedMethodCall(&$object, $methodName, array $parameters = [])
    {
        $reflection = new ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }

    protected function mockEngine($methods = [])
    {
        $methods = array_merge(['createJobId'], $methods);
        $Engine = $this->getMock($this->engineClass, $methods, [$this->Logger, $this->config]);
        $Engine->expects($this->any())
                ->method('createJobId')
                ->will($this->onConsecutiveCalls(1, 2, 3, 4, 5, 6));
        return $Engine;
    }
}
