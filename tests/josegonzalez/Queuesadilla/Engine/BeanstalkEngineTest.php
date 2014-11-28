<?php

namespace josegonzalez\Queuesadilla\Engine;

use \Pheanstalk\Exception\ServerException;
use \josegonzalez\Queuesadilla\Engine\BeanstalkEngine;
use \PHPUnit_Framework_TestCase;

class BeanstalkEngineTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->config = [
            'queue' => 'default',
            'user' => 'travis',
            'pass' => '',
            'url' => getenv('BEANSTALK_URL'),
        ];
        $this->Engine = new BeanstalkEngine($this->config);
    }

    public function tearDown()
    {
        foreach ($this->Engine->queues() as $queue) {
            $this->Engine->connection->useTube($queue);
            try {
                $job = $this->Engine->connection->peekReady();
            } catch (ServerException $e) {
                continue;
            }

            while (!empty($job)) {
                $this->Engine->connection->deleteJob($job);
                try {
                    $job = $this->Engine->connection->peekReady();
                } catch (ServerException $e) {
                    break;
                }
            }
        }
        unset($this->Engine);
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\BeanstalkEngine::__construct
     * @covers josegonzalez\Queuesadilla\Engine\BeanstalkEngine::connected
     */
    public function testConstruct()
    {
        $Engine = new BeanstalkEngine($this->config);
        $this->assertTrue($Engine->connected());
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
     * @covers josegonzalez\Queuesadilla\Utility\Pheanstalk::dispatchCommand
     * @covers josegonzalez\Queuesadilla\Utility\Pheanstalk::deleteJob
     */
    public function testDelete()
    {
        $engineClass = 'josegonzalez\Queuesadilla\Engine\BeanstalkEngine';
        $Engine = $this->getMock($engineClass, ['jobId'], [$this->config]);

        $this->assertFalse($Engine->delete(null));
        $this->assertFalse($Engine->delete(false));
        $this->assertFalse($Engine->delete(1));
        $this->assertFalse($Engine->delete('string'));
        $this->assertFalse($Engine->delete(['key' => 'value']));
        $this->assertFalse($Engine->delete(['id' => '1']));

        $this->assertTrue($Engine->push('some_function'));
        $this->assertTrue($Engine->push('another_function', [], ['queue' => 'other']));
        $item = $Engine->pop();
        $this->assertTrue($Engine->delete($item));
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\BeanstalkEngine::pop
     */
    public function testPop()
    {
        $engineClass = 'josegonzalez\Queuesadilla\Engine\BeanstalkEngine';
        $Engine = $this->getMock($engineClass, ['jobId'], [$this->config]);

        $this->assertNull($Engine->pop('default'));
        $this->assertTrue($Engine->push(null, [], 'default'));

        $item = $Engine->pop('default');
        $this->assertInternalType('array', $item);
        $this->assertArrayHasKey('class', $item);
        $this->assertArrayHasKey('vars', $item);
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
        $this->assertEmpty($pop1['vars']);
        $this->assertNull($pop2);
        $this->assertEquals('yet_another_function', $pop3['class']);
        $this->assertNull($pop4);
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\BeanstalkEngine::release
     * @covers josegonzalez\Queuesadilla\Utility\Pheanstalk::dispatchCommand
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
}
