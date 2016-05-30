<?php
namespace josegonzalez\Queuesadilla\Engine;

use josegonzalez\Queuesadilla\FixtureData;
use josegonzalez\Queuesadilla\TestCase;
use PDO;
use PDOException;
use Psr\Log\NullLogger;

abstract class AbstractPdoEngineTest extends TestCase
{

    /**
     * Sets up the test case. Sub classes need to set the $url and $engineClass
     * properties before calling parent::setUp()
     *
     * @return void
     */
    public function setUp()
    {
        $this->config = ['url' => $this->url];
        $this->Logger = new NullLogger;
        $this->Engine = $this->mockEngine();
        $this->Fixtures = new FixtureData;
        $this->clearEngine();
    }

    /**
     * Used to truncate the jobs table and to reset the auto increment value.
     *
     * @return void
     */
    abstract protected function clearEngine();

    /**
     * Tear Down
     *
     * @return void
     */
    public function tearDown()
    {
        $this->clearEngine();
        unset($this->Engine);
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\PdoEngine::__construct
     */
    public function testConstruct()
    {
        if ($this->Engine->connection() === null) {
            $this->markTestSkipped('No connection to database available');
        }
        $Engine = new $this->engineClass($this->Logger, []);
        $this->assertNull($Engine->connection());

        $Engine = new $this->engineClass($this->Logger, $this->url);
        $this->assertNotNull($Engine->connection());

        $Engine = new $this->engineClass($this->Logger, $this->config);
        $this->assertNotNull($Engine->connection());
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\PdoEngine::connect
     */
    public function testConnect()
    {
        if ($this->Engine->connection() === null) {
            $this->markTestSkipped('No connection to database available');
        }
        $this->assertTrue($this->Engine->connect());
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\Base::getJobClass
     */
    public function testGetJobClass()
    {
        if ($this->Engine->connection() === null) {
            $this->markTestSkipped('No connection to database available');
        }
        $this->assertEquals('\\josegonzalez\\Queuesadilla\\Job\\Base', $this->Engine->getJobClass());
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\PdoEngine::acknowledge
     */
    public function testAcknowledge()
    {
        if ($this->Engine->connection() === null) {
            $this->markTestSkipped('No connection to database available');
        }
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
     * @covers josegonzalez\Queuesadilla\Engine\PdoEngine::reject
     */
    public function testReject()
    {
        if ($this->Engine->connection() === null) {
            $this->markTestSkipped('No connection to database available');
        }
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
     * @covers josegonzalez\Queuesadilla\Engine\PdoEngine::pop
     */
    public function testPop()
    {
        if ($this->Engine->connection() === null) {
            $this->markTestSkipped('No connection to database available');
        }
        $this->assertNull($this->Engine->pop('default'));
        $this->assertTrue($this->Engine->push($this->Fixtures->default['first'], 'default'));
        $this->assertEquals($this->Fixtures->default['first'], $this->Engine->pop('default'));
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\PdoEngine::push
     */
    public function testPush()
    {
        if ($this->Engine->connection() === null) {
            $this->markTestSkipped('No connection to database available');
        }
        $this->assertTrue($this->Engine->push($this->Fixtures->default['first'], 'default'));
        $this->assertTrue($this->Engine->push($this->Fixtures->default['second'], [
            'delay' => 30
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

        $this->assertNotEmpty($pop1['id']);
        $this->assertEquals($this->Fixtures->default['first']['id'], $pop1['id']);
        $this->assertNull($pop1['class']);
        $this->assertEmpty($pop1['args']);
        $this->assertEquals($this->Fixtures->default['fourth']['id'], $pop2['id']);
        $this->assertEquals('yet_another_function', $pop2['class']);
        $this->assertNull($pop3);
        $this->assertNull($pop4);
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\PdoEngine::release
     */
    public function testRelease()
    {
        if ($this->Engine->connection() === null) {
            $this->markTestSkipped('No connection to database available');
        }
        $this->assertFalse($this->Engine->release(null, 'default'));
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\PdoEngine::queues
     */
    public function testQueues()
    {
        if ($this->Engine->connection() === null) {
            $this->markTestSkipped('No connection to database available');
        }
        $this->assertEquals([], $this->Engine->queues());
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

    protected function execute($connection, $sql)
    {
        if ($connection === null) {
            return;
        }

        $sql = trim($sql);
        try {
            $query = $connection->prepare($sql, []);
            $query->setFetchMode(PDO::FETCH_LAZY);
            if (!$query->execute([])) {
                $query->closeCursor();
                return false;
            }
            if (!$query->columnCount()) {
                $query->closeCursor();
                if (!$query->rowCount()) {
                    return true;
                }
            }
            return $query;
        } catch (PDOException $e) {
            $e->queryString = $sql;
            if (isset($query->queryString)) {
                $e->queryString = $query->queryString;
            }
            throw $e;
        }
    }

    protected function mockEngine($methods = null, $config = null)
    {
        if ($config === null) {
            $config = $this->config;
        }
        return $this->getMock($this->engineClass, $methods, [$this->Logger, $config]);
    }
}
