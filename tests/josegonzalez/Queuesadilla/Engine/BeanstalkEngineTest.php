<?php

namespace josegonzalez\Queuesadilla\Engine;

use josegonzalez\Queuesadilla\Engine\BeanstalkEngine;
use Pheanstalk\Exception\ServerException;
use PHPUnit_Framework_TestCase;
use Psr\Log\NullLogger;
use ReflectionClass;

class BeanstalkEngineTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->url = getenv('BEANSTALK_URL');
        $this->config = ['url' => $this->url];
        $this->Logger = new NullLogger;
        $this->engineClass = 'josegonzalez\Queuesadilla\Engine\BeanstalkEngine';
        $this->Engine = $this->mockEngine();
        $this->clearEngine();
    }

    public function tearDown()
    {
        $this->clearEngine();
        unset($this->Engine);
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\BeanstalkEngine::__construct
     */
    public function testConstruct()
    {
        $Engine = new BeanstalkEngine($this->Logger, []);
        $this->assertNotNull($Engine->connection());

        $Engine = new BeanstalkEngine($this->Logger, $this->url);
        $this->assertNotNull($Engine->connection());

        $Engine = new BeanstalkEngine($this->Logger, $this->config);
        $this->assertNotNull($Engine->connection());
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\BeanstalkEngine::connect
     */
    public function testConnect()
    {
        $this->assertTrue($this->Engine->connect());
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\BeanstalkEngine::getJobClass
     */
    public function testGetJobClass()
    {
        $this->assertEquals('\\josegonzalez\\Queuesadilla\\Job\\BeanstalkJob', $this->Engine->getJobClass());
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\BeanstalkEngine::delete
     * @covers josegonzalez\Queuesadilla\Utility\Pheanstalk::deleteJob
     * @covers josegonzalez\Queuesadilla\Utility\Pheanstalk::protectedMethodCall
     */
    public function testDelete()
    {
        $engineClass = 'josegonzalez\Queuesadilla\Engine\BeanstalkEngine';
        $Engine = $this->getMock($engineClass, ['createJobId'], [$this->Logger, $this->config]);

        $this->assertFalse($Engine->delete(null));
        $this->assertFalse($Engine->delete(false));
        $this->assertFalse($Engine->delete(1));
        $this->assertFalse($Engine->delete('string'));
        $this->assertFalse($Engine->delete(['key' => 'value']));
        $this->assertFalse($Engine->delete(['id' => '1', 'queue' => 'default']));

        $this->assertTrue($Engine->push('some_function'));
        $job = new \Pheanstalk\Job($Engine->lastJobId(), ['queue' => 'default']);
        $this->assertTrue($Engine->push('another_function', [], ['queue' => 'other']));
        $this->assertTrue($Engine->delete(['id' => $job->getId(), 'queue' => 'default', 'job' => $job]));
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\BeanstalkEngine::pop
     */
    public function testPop()
    {
        $engineClass = 'josegonzalez\Queuesadilla\Engine\BeanstalkEngine';
        $Engine = $this->getMock($engineClass, ['createJobId'], [$this->Logger, $this->config]);

        $this->assertNull($Engine->pop('default'));
        $this->assertTrue($Engine->push(null, [], 'default'));

        $item = $Engine->pop('default');
        $this->assertInternalType('array', $item);
        $this->assertArrayHasKey('class', $item);
        $this->assertArrayHasKey('args', $item);
        $this->assertArrayHasKey('job', $item);
        $this->assertInstanceOf('\Pheanstalk\Job', $item['job']);
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\BeanstalkEngine::push
     * @covers josegonzalez\Queuesadilla\Engine\BeanstalkEngine::pop
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
        $this->assertEmpty($pop1['args']);
        $this->assertNull($pop2);
        $this->assertEquals('yet_another_function', $pop3['class']);
        $this->assertNull($pop4);
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\BeanstalkEngine::release
     * @covers josegonzalez\Queuesadilla\Utility\Pheanstalk::protectedMethodCall
     * @covers josegonzalez\Queuesadilla\Utility\Pheanstalk::releaseJob
     */
    public function testRelease()
    {
        $this->assertTrue($this->Engine->push(null, [], 'default'));

        $item = $this->Engine->pop('default');
        $this->assertInstanceOf('\Pheanstalk\Job', $item['job']);
        $this->assertTrue($this->Engine->release($item, 'default'));
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\BeanstalkEngine::queues
     */
    public function testQueues()
    {
        $this->assertEquals(['default'], $this->Engine->queues());
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
        foreach ($this->Engine->queues() as $queue) {
            $this->Engine->connection()->useTube($queue);
            try {
                $job = $this->Engine->connection()->peekReady($queue);
            } catch (ServerException $e) {
                continue;
            }

            while (!empty($job)) {
                $this->Engine->connection()->deleteJob($job);
                try {
                    $job = $this->Engine->connection()->peekReady($queue);
                } catch (ServerException $e) {
                    break;
                }
            }
        }
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
