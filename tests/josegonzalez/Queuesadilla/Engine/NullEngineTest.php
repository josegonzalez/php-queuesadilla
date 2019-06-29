<?php

namespace josegonzalez\Queuesadilla\Engine;

use josegonzalez\Queuesadilla\Engine\NullEngine;
use josegonzalez\Queuesadilla\FixtureData;
use josegonzalez\Queuesadilla\TestCase;
use Psr\Log\NullLogger;

class NullEngineTest extends TestCase
{
    public function setUp() : void
    {
        $this->url = getenv('NULL_URL');
        $this->config = ['url' => $this->url];
        $this->Logger = new NullLogger;
        $this->engineClass = 'josegonzalez\Queuesadilla\Engine\NullEngine';
        $this->Engine = $this->mockEngine();
        $this->Fixtures = new FixtureData;
    }

    public function tearDown() : void
    {
        unset($this->Engine);
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\Base::__construct
     * @covers josegonzalez\Queuesadilla\Engine\Base::connection
     * @covers josegonzalez\Queuesadilla\Engine\NullEngine::__construct
     * @covers josegonzalez\Queuesadilla\Engine\NullEngine::connection
     */
    public function testConstruct()
    {
        $Engine = new NullEngine($this->Logger, []);
        $this->assertNotNull($Engine->connection());

        $Engine = new NullEngine($this->Logger, $this->url);
        $this->assertNotNull($Engine->connection());

        $Engine = new NullEngine($this->Logger, $this->config);
        $this->assertNotNull($Engine->connection());
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\Base::connect
     * @covers josegonzalez\Queuesadilla\Engine\NullEngine::connect
     */
    public function testConnect()
    {
        $this->assertTrue($this->Engine->connect());

        $this->Engine->return = false;
        $this->assertFalse($this->Engine->connect());
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\Base::getJobClass
     * @covers josegonzalez\Queuesadilla\Engine\NullEngine::getJobClass
     */
    public function testGetJobClass()
    {
        $this->assertEquals('\\josegonzalez\\Queuesadilla\\Job\\Base', $this->Engine->getJobClass());
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\Base::lastJobId
     * @covers josegonzalez\Queuesadilla\Engine\NullEngine::lastJobId
     */
    public function testLastJobId()
    {
        $this->assertNull($this->Engine->lastJobId());
        $this->assertTrue($this->Engine->push(null, 'default'));
        $this->assertTrue($this->Engine->lastJobId());
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\Base::acknowledge
     * @covers josegonzalez\Queuesadilla\Engine\NullEngine::acknowledge
     */
    public function testAcknowledge()
    {
        $this->assertFalse($this->Engine->acknowledge(null));
        $this->assertFalse($this->Engine->acknowledge(false));
        $this->assertFalse($this->Engine->acknowledge(1));
        $this->assertFalse($this->Engine->acknowledge('string'));
        $this->assertFalse($this->Engine->acknowledge(['key' => 'value']));

        $this->assertTrue($this->Engine->acknowledge($this->Fixtures->default['first']));
        $this->Engine->return = false;
        $this->assertFalse($this->Engine->acknowledge(null));
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\Base::reject
     * @covers josegonzalez\Queuesadilla\Engine\NullEngine::reject
     */
    public function testReject()
    {
        $this->assertFalse($this->Engine->reject(null));
        $this->assertFalse($this->Engine->reject(false));
        $this->assertFalse($this->Engine->reject(1));
        $this->assertFalse($this->Engine->reject('string'));
        $this->assertFalse($this->Engine->reject(['key' => 'value']));

        $this->assertTrue($this->Engine->reject($this->Fixtures->default['first']));
        $this->Engine->return = false;
        $this->assertFalse($this->Engine->reject(null));
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\Base::config
     * @covers josegonzalez\Queuesadilla\Engine\NullEngine::config
     */
    public function testConfig()
    {
        $this->assertEquals([
            'queue' => 'default',
            'timeout' => '1',
            'scheme' => 'null',
            'database' => false,
        ], $this->Engine->config());
        $this->assertEquals([
            'queue' => 'other',
            'timeout' => '1',
            'scheme' => 'null',
            'database' => false,
        ], $this->Engine->config(['queue' => 'other']));
        $this->assertEquals('other', $this->Engine->config('queue'));
        $this->assertEquals('another', $this->Engine->config('queue', 'another'));
        $this->assertEquals(null, $this->Engine->config('random'));
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\Base::setting
     * @covers josegonzalez\Queuesadilla\Engine\NullEngine::setting
     */
    public function testSetting()
    {
        $this->assertEquals('string_to_array', $this->Engine->setting('string_to_array', 'queue'));
        $this->assertEquals('non_default', $this->Engine->setting(['queue' => 'non_default'], 'queue'));
        $this->assertEquals('default', $this->Engine->setting([], 'queue'));
        $this->assertEquals('other', $this->Engine->setting([], 'other', 'other'));
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\Base::pop
     * @covers josegonzalez\Queuesadilla\Engine\NullEngine::pop
     */
    public function testPop()
    {
        $this->assertTrue($this->Engine->pop('default'));

        $this->Engine->return = false;
        $this->assertFalse($this->Engine->pop('default'));
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\Base::push
     * @covers josegonzalez\Queuesadilla\Engine\NullEngine::push
     */
    public function testPush()
    {
        $this->assertTrue($this->Engine->push(null, 'default'));

        $this->Engine->return = false;
        $this->assertFalse($this->Engine->connect(null, 'default'));
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\Base::release
     * @covers josegonzalez\Queuesadilla\Engine\NullEngine::release
     */
    public function testRelease()
    {
        $this->assertTrue($this->Engine->release(null, 'default'));

        $this->Engine->return = false;
        $this->assertFalse($this->Engine->release(null, 'default'));
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\Base::queues
     * @covers josegonzalez\Queuesadilla\Engine\NullEngine::queues
     */
    public function testQueues()
    {
        $this->assertEquals([], $this->Engine->queues());
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
