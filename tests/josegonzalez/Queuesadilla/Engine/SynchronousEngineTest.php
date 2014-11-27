<?php

namespace josegonzalez\Queuesadilla\Engine;

use \DateTime;
use \DateInterval;
use \josegonzalez\Queuesadilla\Engine\SynchronousEngine;
use \josegonzalez\Queuesadilla\Worker\SequentialWorker;
use \josegonzalez\Queuesadilla\Worker\TestWorker;
use \PHPUnit_Framework_TestCase;
use \ReflectionClass;

class SynchronousEngineTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $engineClass = 'josegonzalez\Queuesadilla\Engine\SynchronousEngine';
        $this->Engine = $this->getMock($engineClass, ['getWorker', 'jobId']);
        $this->Engine->expects($this->any())
                ->method('getWorker')
                ->will($this->returnValue(new TestWorker($this->Engine)));
        $this->Engine->expects($this->any())
                ->method('jobId')
                ->will($this->onConsecutiveCalls('1', '2', '3', '4'));
    }

    public function tearDown()
    {
        unset($this->Engine);
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\SynchronousEngine::push
     * @covers josegonzalez\Queuesadilla\Engine\MemoryEngine::push
     * @covers josegonzalez\Queuesadilla\Engine\MemoryEngine::pop
     */
    public function testPush()
    {
        $this->assertEquals([
            'id' => '1',
            'class' => null,
            'vars' => [],
            'options' => ['queue' => 'default'],
        ], $this->Engine->push(null, [], 'default'));
        $this->assertNull($this->Engine->push('some_function', [], ['delay' => 30]));

        $datetime = new DateTime();
        $this->assertEquals([
            'id' => '3',
            'class' => 'another_function',
            'vars' => [],
            'options' => [
              'queue' => 'default',
              'expires_at' => $datetime->add(new DateInterval(sprintf('PT%sS', 1)))
            ],
        ], $this->Engine->push('another_function', [], ['expires_in' => 1]));
        $this->assertEquals([
            'id' => '4',
            'class' => 'yet_another_function',
            'vars' => [],
            'options' => ['queue' => 'default'],
        ], $this->Engine->push('yet_another_function', [], 'default'));

        sleep(2);

        $this->assertNull($this->Engine->pop());
        $this->assertNull($this->Engine->pop());
        $this->assertNull($this->Engine->pop());
        $this->assertNull($this->Engine->pop());
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\SynchronousEngine::getWorker
     */
    public function testGetWorker()
    {
        $Engine = new SynchronousEngine();
        $this->assertInstanceOf(
            '\josegonzalez\Queuesadilla\Worker\SequentialWorker',
            $this->protectedMethodCall($Engine, 'getWorker')
        );
    }

    public function protectedMethodCall(&$object, $methodName, array $parameters = [])
    {
        $reflection = new ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }
}
