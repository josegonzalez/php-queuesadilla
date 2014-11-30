<?php

namespace josegonzalez\Queuesadilla\Engine;

use josegonzalez\Queuesadilla\Engine\RedisEngine;
use PHPUnit_Framework_TestCase;
use Psr\Log\NullLogger;
use RedisException;
use ReflectionClass;

class RedisEngineTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->url = getenv('REDIS_URL');
        $this->config = ['url' => $this->url];
        $this->Logger = new NullLogger;
        $this->engineClass = 'josegonzalez\Queuesadilla\Engine\RedisEngine';
        $this->Engine = $this->mockEngine();
        $this->clearEngine();
    }

    public function tearDown()
    {
        $this->clearEngine();
        unset($this->Engine);
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\RedisEngine::__construct
     * @covers josegonzalez\Queuesadilla\Engine\RedisEngine::connected
     */
    public function testConstruct()
    {
        $Engine = new RedisEngine($this->Logger, []);
        $this->assertTrue($Engine->connected());

        $Engine = new RedisEngine($this->Logger, $this->url);
        $this->assertTrue($Engine->connected());

        $Engine = new RedisEngine($this->Logger, $this->config);
        $this->assertTrue($Engine->connected());
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\RedisEngine::connect
     * @covers josegonzalez\Queuesadilla\Engine\RedisEngine::redisInstance
     */
    public function testConnect()
    {
        $this->assertTrue($this->Engine->connect());

        $engineClass = 'josegonzalez\Queuesadilla\Engine\RedisEngine';

        $config = $this->config;
        $config['pass'] = 'some_password';
        $Engine = $this->getMock($engineClass, ['createJobId'], [$this->Logger, $config]);
        $this->assertFalse($Engine->connect());

        $config = $this->config;
        $config['database'] = 1;
        $Engine = $this->getMock($engineClass, ['createJobId'], [$this->Logger, $config]);
        $this->assertTrue($Engine->connect());

        $config = $this->config;
        $config['persistent'] = false;
        $Engine = $this->getMock($engineClass, ['createJobId'], [$this->Logger, $config]);
        $this->assertTrue($Engine->connect());
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\RedisEngine::connect
     */
    public function testConnectionException()
    {
        $engineClass = '\josegonzalez\Queuesadilla\Engine\RedisEngine';
        $Engine = $this->getMock($engineClass, ['redisInstance'], [$this->Logger, $this->config]);
        $Engine->expects($this->once())
                ->method('redisInstance')
                ->will($this->throwException(new RedisException));

        $this->assertFalse($Engine->connect());
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\Base::getJobClass
     */
    public function testGetJobClass()
    {
        $this->assertEquals('\\josegonzalez\\Queuesadilla\\Job\\Base', $this->Engine->getJobClass());
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
        $this->assertFalse($this->Engine->delete(['key' => 'value']));
        $this->assertFalse($this->Engine->delete(['id' => 1, 'queue' => 'default']));

        $this->assertTrue($this->Engine->push('some_function'));
        $this->assertTrue($this->Engine->push('another_function', [], ['queue' => 'other']));
        $this->assertTrue($this->Engine->delete(['id' => 1, 'queue' => 'default']));
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\RedisEngine::createJobId
     */
    public function testJobId()
    {
        $this->assertInternalType('int', $this->protectedMethodCall($this->Engine, 'createJobId'));
        $this->assertInternalType('int', $this->protectedMethodCall($this->Engine, 'createJobId'));
        $this->assertInternalType('int', $this->protectedMethodCall($this->Engine, 'createJobId'));
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\RedisEngine::pop
     */
    public function testPop()
    {
        $this->assertNull($this->Engine->pop('default'));
        $this->assertEquals(1, $this->Engine->push(null, [], 'default'));
        $this->assertEquals([
            'id' => 1,
            'class' => null,
            'vars' => [],
            'queue' => 'default',
        ], $this->Engine->pop('default'));
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
        $this->assertEquals(2, $this->Engine->push('some_function', [], [
            'delay' => 30,
        ]));
        $this->assertEquals(3, $this->Engine->push('another_function', [], [
            'expires_in' => 1,
        ]));
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
        $this->assertEquals([
            'id' => 1,
            'class' => null,
            'vars' => [],
            'queue' => 'default',
        ], $this->Engine->pop('default'));

        $this->assertFalse($this->Engine->release(null, 'default'));

        $this->assertEquals(1, $this->Engine->release([
            'id' => 2,
            'class' => 'some_function',
            'vars' => [],
            'queue' => 'default',
        ], 'default'));
        $this->assertEquals([
            'id' => 2,
            'class' => 'some_function',
            'vars' => [],
            'queue' => 'default',
        ], $this->Engine->pop('default'));
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\RedisEngine::queues
     * @covers josegonzalez\Queuesadilla\Engine\RedisEngine::requireQueue
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

    protected function clearEngine()
    {
        $this->Engine->connection()->flushdb();
        $this->Engine->connection()->script('flush');
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
