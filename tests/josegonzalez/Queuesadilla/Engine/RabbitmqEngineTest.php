<?php

namespace josegonzalez\Queuesadilla\Engine;

use josegonzalez\Queuesadilla\Engine\RabbitmqEngine;
use josegonzalez\Queuesadilla\FixtureData;
use josegonzalez\Queuesadilla\TestCase;
use Psr\Log\NullLogger;
use RedisException;

class RabbitmqEngineTest extends TestCase
{
    public function setUp()
    {
        $this->url = getenv('RABBITMQ_URL');
        $this->config = ['url' => $this->url];
        $this->Logger = new NullLogger;
        $this->engineClass = 'josegonzalez\Queuesadilla\Engine\RabbitmqEngine';
        $this->Engine = $this->mockEngine();
        $this->Fixtures = new FixtureData;
        $this->clearEngine();
    }

    public function tearDown()
    {
        $this->clearEngine();
        unset($this->Engine);
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\RabbitmqEngine::__construct
     */
    public function testConstruct()
    {
        $Engine = new RabbitmqEngine($this->Logger, []);
        $this->assertNotNull($Engine->connection());

        $Engine = new RabbitmqEngine($this->Logger, $this->url);
        $this->assertNotNull($Engine->connection());

        $Engine = new RabbitmqEngine($this->Logger, $this->config);
        $this->assertNotNull($Engine->connection());
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\RabbitmqEngine::connect
     */
    public function testConnect()
    {
        $this->assertTrue($this->Engine->connect());

        $config = $this->config;
        $Engine = $this->mockEngine(null, $config);
        $Engine->config('pass', 'some_password');
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
     * @covers josegonzalez\Queuesadilla\Engine\RabbitmqEngine::acknowledge
     */
    public function testAcknowledge()
    {
        $this->assertFalse($this->Engine->acknowledge(null));
        $this->assertFalse($this->Engine->acknowledge(false));
        $this->assertFalse($this->Engine->acknowledge(1));
        $this->assertFalse($this->Engine->acknowledge('string'));
        $this->assertFalse($this->Engine->acknowledge(['key' => 'value']));
        $this->assertFalse($this->Engine->acknowledge($this->Fixtures->default['first']));

        $this->assertTrue($this->Engine->push($this->Fixtures->default['first']));
        $this->assertTrue($this->Engine->push($this->Fixtures->other['third']));
        $this->assertTrue($this->Engine->acknowledge($this->Fixtures->default['first']));
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\RabbitmqEngine::reject
     */
    public function testReject()
    {
        $this->assertFalse($this->Engine->reject(null));
        $this->assertFalse($this->Engine->reject(false));
        $this->assertFalse($this->Engine->reject(1));
        $this->assertFalse($this->Engine->reject('string'));
        $this->assertFalse($this->Engine->reject(['key' => 'value']));
        $this->assertFalse($this->Engine->reject($this->Fixtures->default['first']));

        $this->assertTrue($this->Engine->push($this->Fixtures->default['first']));
        $this->assertTrue($this->Engine->push($this->Fixtures->other['third']));
        $this->assertTrue($this->Engine->reject($this->Fixtures->default['first']));
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\RabbitmqEngine::pop
     */
    public function testPop()
    {
        $this->assertNull($this->Engine->pop('default'));
        $this->assertTrue($this->Engine->push($this->Fixtures->default['first'], 'default'));
        $this->assertEquals($this->Fixtures->default['first'], $this->Engine->pop('default'));
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\RabbitmqEngine::push
     */
    public function testPush()
    {
        $this->assertTrue($this->Engine->push($this->Fixtures->default['first'], 'default'));
        $this->assertTrue($this->Engine->push($this->Fixtures->default['second'], [
            'delay' => 30,
        ]));
        $this->assertTrue($this->Engine->push($this->Fixtures->other['third'], [
            'expires_in' => 1,
        ]));
        $this->assertTrue($this->Engine->push($this->Fixtures->default['fourth'], 'default'));

        sleep(2);

        $pop1 = $this->Engine->pop();
        $pop2 = $this->Engine->pop();
        $pop3 = $this->Engine->pop();
        $pop4 = $this->Engine->pop();

        $this->assertNull($pop1['class']);
        $this->assertEmpty($pop1['args']);

        $this->markTestIncomplete(
            'RabbitmqEngine does not yet implement delay or expires_in (tbd sorted sets)'
        );

        $this->assertEquals('yet_another_function', $pop2['class']);
        $this->assertNull($pop3);
        $this->assertNull($pop4);
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\RabbitmqEngine::pop
     * @covers josegonzalez\Queuesadilla\Engine\RabbitmqEngine::release
     */
    public function testRelease()
    {
        $this->assertTrue($this->Engine->push($this->Fixtures->default['first'], 'default'));
        $this->assertEquals($this->Fixtures->default['first'], $this->Engine->pop('default'));

        $this->assertFalse($this->Engine->release(null, 'default'));

        $this->assertEquals(1, $this->Engine->release($this->Fixtures->default['second'], 'default'));
        $this->assertEquals($this->Fixtures->default['second'], $this->Engine->pop('default'));
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\RabbitmqEngine::queues
     */
    public function testQueues()
    {
        $this->assertEquals(['default'], $this->Engine->queues());
        $this->Engine->push($this->Fixtures->default['first']);
        $this->assertEquals(['default'], $this->Engine->queues());

        $this->Engine->push($this->Fixtures->other['second'], ['queue' => 'other']);
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
    }

    protected function mockEngine($methods = null, $config = null)
    {
        if ($config === null) {
            $config = $this->config;
        }

        return $this->getMockBuilder($this->engineClass)
            ->setMethods($methods)
            ->setConstructorArgs([$this->Logger, $config])
            ->getMock();
    }
}
