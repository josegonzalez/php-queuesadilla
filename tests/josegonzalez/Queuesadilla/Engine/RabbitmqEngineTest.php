<?php

namespace josegonzalez\Queuesadilla\Engine;

use Exception;
use josegonzalez\Queuesadilla\Engine\RabbitmqEngine;
use josegonzalez\Queuesadilla\FixtureData;
use josegonzalez\Queuesadilla\TestCase;
use Psr\Log\NullLogger;

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
    }

    public function tearDown()
    {
        $this->clearEngine();
        // unset($this->Engine);
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
        $this->assertTrue($this->Engine->isConnected());
        $data = $this->Engine->pop(['acknowledge' => false]);
        $this->assertTrue($this->Engine->acknowledge($data));

        $this->Engine->pop();
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
        $this->assertTrue($this->Engine->reject($this->Engine->pop(['acknowledge' => false])));
        $this->assertTrue($this->Engine->reject($this->Engine->pop(['acknowledge' => false])));
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\RabbitmqEngine::pop
     */
    public function testPop()
    {
        $expected = $this->Fixtures->default['first'];
        $this->assertNull($this->Engine->pop('default'));
        $this->assertTrue($this->Engine->push($this->Fixtures->default['first'], 'default'));

        $actual = $this->Engine->pop('default');
        unset($actual['_delivery_tag'], $actual['_message']);
        $this->assertEquals($expected, $actual);
        $this->assertNull($this->Engine->pop('default'));
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
            'RabbitmqEngine does not yet implement delay or expires_in'
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

        $actual = $this->Engine->pop('default');
        unset($actual['_delivery_tag'], $actual['_message']);
        $this->assertEquals($this->Fixtures->default['first'], $actual);

        $this->assertFalse($this->Engine->release(null, 'default'));

        $this->assertTrue($this->Engine->push($this->Fixtures->default['second'], 'default'));

        $actual = $this->Engine->pop('default');
        unset($actual['_delivery_tag'], $actual['_message']);
        $this->assertEquals($this->Fixtures->default['second'], $actual);
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
        $this->Engine->pop(['queue' => 'other']);
        $queues = $this->Engine->queues();
        sort($queues);
        $this->assertEquals(['default', 'other'], $queues);
    }

    protected function clearEngine()
    {
        if ($this->Engine->connection() !== null) {
            foreach (['default', 'other'] as $queue) {
                try {
                    $this->Engine->channel->queuePurge($queue);
                } catch (Exception $e) {
                }
            }
            $this->Engine->channel->close();
            $connection = $this->Engine->connection();
            if (method_exists($connection, 'close')) {
                $connection->close();
            } else {
                $connection->disconnect();
            }
        }
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
