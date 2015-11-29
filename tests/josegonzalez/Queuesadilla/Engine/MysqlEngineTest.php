<?php

namespace josegonzalez\Queuesadilla\Engine;

use josegonzalez\Queuesadilla\Engine\MysqlEngine;
use josegonzalez\Queuesadilla\FixtureData;
use josegonzalez\Queuesadilla\TestCase;
use PDO;
use PDOException;
use Psr\Log\NullLogger;

class MysqlEngineTest extends TestCase
{
    public function setUp()
    {
        $this->url = getenv('MYSQL_URL');
        $this->config = ['url' => $this->url];
        $this->Logger = new NullLogger;
        $this->engineClass = 'josegonzalez\Queuesadilla\Engine\MysqlEngine';
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
     * @covers josegonzalez\Queuesadilla\Engine\MysqlEngine::__construct
     */
    public function testConstruct()
    {
        if ($this->Engine->connection() === null) {
            $this->markTestSkipped('No connection to mysql available');
        }
        $Engine = new MysqlEngine($this->Logger, []);
        $this->assertNull($Engine->connection());

        $Engine = new MysqlEngine($this->Logger, $this->url);
        $this->assertNotNull($Engine->connection());

        $Engine = new MysqlEngine($this->Logger, $this->config);
        $this->assertNotNull($Engine->connection());
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\MysqlEngine::connect
     */
    public function testConnect()
    {
        if ($this->Engine->connection() === null) {
            $this->markTestSkipped('No connection to mysql available');
        }
        $this->assertTrue($this->Engine->connect());
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\Base::getJobClass
     */
    public function testGetJobClass()
    {
        if ($this->Engine->connection() === null) {
            $this->markTestSkipped('No connection to mysql available');
        }
        $this->assertEquals('\\josegonzalez\\Queuesadilla\\Job\\Base', $this->Engine->getJobClass());
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\MysqlEngine::acknowledge
     */
    public function testAcknowledge()
    {
        if ($this->Engine->connection() === null) {
            $this->markTestSkipped('No connection to mysql available');
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
     * @covers josegonzalez\Queuesadilla\Engine\MysqlEngine::reject
     */
    public function testReject()
    {
        if ($this->Engine->connection() === null) {
            $this->markTestSkipped('No connection to mysql available');
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
     * @covers josegonzalez\Queuesadilla\Engine\MysqlEngine::pop
     */
    public function testPop()
    {
        if ($this->Engine->connection() === null) {
            $this->markTestSkipped('No connection to mysql available');
        }
        $this->assertNull($this->Engine->pop('default'));
        $this->assertTrue($this->Engine->push($this->Fixtures->default['first'], 'default'));
        $this->assertEquals($this->Fixtures->default['first'], $this->Engine->pop('default'));
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\MysqlEngine::push
     */
    public function testPush()
    {
        if ($this->Engine->connection() === null) {
            $this->markTestSkipped('No connection to mysql available');
        }
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

        $this->assertNotEmpty($pop1['id']);
        $this->assertNull($pop1['class']);
        $this->assertEmpty($pop1['args']);
        $this->assertEquals('yet_another_function', $pop2['class']);
        $this->assertNull($pop3);
        $this->assertNull($pop4);
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\MysqlEngine::release
     */
    public function testRelease()
    {
        if ($this->Engine->connection() === null) {
            $this->markTestSkipped('No connection to mysql available');
        }
        $this->assertFalse($this->Engine->release(null, 'default'));
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\MysqlEngine::queues
     */
    public function testQueues()
    {
        if ($this->Engine->connection() === null) {
            $this->markTestSkipped('No connection to mysql available');
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

    protected function clearEngine()
    {
        $this->execute($this->Engine->connection(), 'TRUNCATE TABLE jobs');
    }

    protected function mockEngine($methods = null, $config = null)
    {
        if ($config === null) {
            $config = $this->config;
        }
        return $this->getMock($this->engineClass, $methods, [$this->Logger, $config]);
    }
}
