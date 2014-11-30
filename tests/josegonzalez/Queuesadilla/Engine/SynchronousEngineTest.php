<?php

namespace josegonzalez\Queuesadilla\Engine;

use DateTime;
use DateInterval;
use josegonzalez\Queuesadilla\Engine\SynchronousEngine;
use josegonzalez\Queuesadilla\Worker\SequentialWorker;
use josegonzalez\Queuesadilla\Worker\TestWorker;
use PHPUnit_Framework_TestCase;
use Psr\Log\NullLogger;
use ReflectionClass;

class SynchronousEngineTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->url = getenv('SYNCHRONOUS_URL');
        $this->config = ['url' => $this->url];
        $this->Logger = new NullLogger;
        $this->engineClass = 'josegonzalez\Queuesadilla\Engine\SynchronousEngine';
        $this->Engine = $this->mockEngine(['getWorker']);
        $this->Engine->expects($this->any())
                ->method('getWorker')
                ->will($this->returnValue(new TestWorker($this->Engine)));
    }

    public function tearDown()
    {
        unset($this->Engine);
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\SynchronousEngine::__construct
     */
    public function testConstruct()
    {
        $Engine = new SynchronousEngine($this->Logger, []);
        $this->assertNotNull($Engine->connection());

        $Engine = new SynchronousEngine($this->Logger, $this->url);
        $this->assertNotNull($Engine->connection());

        $Engine = new SynchronousEngine($this->Logger, $this->config);
        $this->assertNotNull($Engine->connection());
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\SynchronousEngine::connect
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
     * @covers josegonzalez\Queuesadilla\Engine\SynchronousEngine::delete
     */
    public function testDelete()
    {
        $Engine = $this->mockEngine();
        $this->assertFalse($Engine->delete(null));
        $this->assertFalse($Engine->delete(false));
        $this->assertFalse($Engine->delete(1));
        $this->assertFalse($Engine->delete('string'));
        $this->assertFalse($Engine->delete(['key' => 'value']));
        $this->assertFalse($Engine->delete(['id' => 1, 'queue' => 'default']));

        $this->assertTrue($Engine->push('some_function'));
        $this->assertTrue($Engine->push('another_function', [], ['queue' => 'other']));
        $this->assertFalse($Engine->delete(['id' => 1, 'queue' => 'default']));
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\SynchronousEngine::push
     * @covers josegonzalez\Queuesadilla\Engine\MemoryEngine::push
     * @covers josegonzalez\Queuesadilla\Engine\MemoryEngine::pop
     */
    public function testPush()
    {
        $this->assertEquals([
            'id' => 1,
            'class' => null,
            'vars' => [],
            'options' => [],
            'queue' => 'default',
        ], $this->Engine->push(null, []));
        $this->assertNull($this->Engine->push('some_function', [], ['delay' => 30]));
        $datetime = new DateTime;
        $this->assertEquals([
            'id' => 3,
            'class' => null,
            'vars' => [],
            'options' => [
              'delay_until' => $datetime->add(new DateInterval(sprintf('PT%sS', 0)))
            ],
            'queue' => 'default',
        ], $this->Engine->push(null, [], ['delay' => 0]));

        $datetime = new DateTime;
        $this->assertEquals([
            'id' => 4,
            'class' => 'another_function',
            'vars' => [],
            'options' => [
              'expires_at' => $datetime->add(new DateInterval(sprintf('PT%sS', 1)))
            ],
            'queue' => 'default',
        ], $this->Engine->push('another_function', [], ['expires_in' => 1]));
        $this->assertEquals([
            'id' => 5,
            'class' => 'yet_another_function',
            'vars' => [],
            'options' => [],
            'queue' => 'default',
        ], $this->Engine->push('yet_another_function', []));
        $this->assertEquals([
            'id' => 6,
            'class' => 'another_function',
            'vars' => [],
            'options' => [
              'expires_at' => $datetime->add(new DateInterval(sprintf('PT%sS', 0)))
            ],
            'queue' => 'default',
        ], $this->Engine->push('another_function', [], ['expires_in' => 1]));

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
        $Engine = new SynchronousEngine;
        $this->assertInstanceOf(
            '\josegonzalez\Queuesadilla\Worker\SequentialWorker',
            $this->protectedMethodCall($Engine, 'getWorker')
        );
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
