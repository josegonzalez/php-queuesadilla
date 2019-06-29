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
    public function setUp() : void
    {
        $this->config = ['url' => $this->url];
        $this->Logger = new NullLogger;
        $this->Engine = $this->mockEngine();
        $this->Fixtures = new FixtureData;
        $this->clearEngine();
        $this->expandFixtureData();
    }

    /**
     * Tear Down
     *
     * @return void
     */
    public function tearDown() : void
    {
        $this->clearEngine();
        unset($this->Engine);
    }

    /**
     * Used to truncate the jobs table and to reset the auto increment value.
     *
     * @return void
     */
    abstract protected function clearEngine();

    /**
     * @covers josegonzalez\Queuesadilla\Engine\Base::__construct
     * @covers josegonzalez\Queuesadilla\Engine\PdoEngine::__construct
     * @covers josegonzalez\Queuesadilla\Engine\MysqlEngine::__construct
     * @covers josegonzalez\Queuesadilla\Engine\PostgresEngine::__construct
     */
    public function testConstruct()
    {
        if ($this->Engine->connection() === null) {
            $this->markTestSkipped('No connection to database available');
        }
        $Engine = new $this->engineClass($this->Logger, [
            'user' => 'invalid'
        ]);
        $this->assertNull($Engine->connection());

        $Engine = new $this->engineClass($this->Logger, $this->url);
        $this->assertNotNull($Engine->connection());

        $Engine = new $this->engineClass($this->Logger, $this->config);
        $this->assertNotNull($Engine->connection());
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\PdoEngine::connect
     * @covers josegonzalez\Queuesadilla\Engine\MysqlEngine::connect
     * @covers josegonzalez\Queuesadilla\Engine\PostgresEngine::connect
     */
    public function testConnect()
    {
        if ($this->Engine->connection() === null) {
            $this->markTestSkipped('No connection to database available');
        }
        $engine = $this->mockEngine(null, [
            'database' => 'invalid',
            'user' => 'invalid'
        ]);

        $this->assertFalse($engine->connect());

        $this->assertTrue($this->Engine->connect());
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\Base::connection
     */
    public function testConnection()
    {
        $engine = $this->mockEngine();
        $connection = $engine->connection();
        $this->assertInstanceOf('PDO', $connection);
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\Base::getJobClass
     * @covers josegonzalez\Queuesadilla\Engine\MysqlEngine::getJobClass
     * @covers josegonzalez\Queuesadilla\Engine\PostgresEngine::getJobClass
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
     * @covers josegonzalez\Queuesadilla\Engine\MysqlEngine::acknowledge
     * @covers josegonzalez\Queuesadilla\Engine\PostgresEngine::acknowledge
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
     * @covers josegonzalez\Queuesadilla\Engine\MysqlEngine::reject
     * @covers josegonzalez\Queuesadilla\Engine\PostgresEngine::reject
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
     * @covers josegonzalez\Queuesadilla\Engine\MysqlEngine::pop
     * @covers josegonzalez\Queuesadilla\Engine\PostgresEngine::pop
     */
    public function testPop()
    {
        if ($this->Engine->connection() === null) {
            $this->markTestSkipped('No connection to database available');
        }
        $this->assertNull($this->Engine->pop('default'));
        $this->assertTrue($this->Engine->push($this->Fixtures->default['first'], ['queue' => 'default', 'priority' => 4]));
        $this->assertTrue($this->Engine->push($this->Fixtures->default['second'], ['queue' => 'default', 'priority' => 1]));
        $this->assertTrue($this->Engine->push($this->Fixtures->default['third'], ['queue' => 'default', 'priority' => 3]));
        $msg = 'We should have returned the second job, as it has the lowest priority';
        $secondJobFixture = $this->Fixtures->default['second'];
        $secondJobFixture['options']['priority'] = 1;
        $this->assertEquals($secondJobFixture, $this->Engine->pop('default'), $msg);
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\PdoEngine::pop
     * @covers josegonzalez\Queuesadilla\Engine\PdoEngine::generatePopSelectSql
     * @covers josegonzalez\Queuesadilla\Engine\PdoEngine::generatePopOrderSql
     * @covers josegonzalez\Queuesadilla\Engine\PdoEngine::formattedDateNow
     * @covers josegonzalez\Queuesadilla\Engine\MysqlEngine::pop
     * @covers josegonzalez\Queuesadilla\Engine\PostgresEngine::pop
     */
    public function testPopFIFO()
    {
        if ($this->Engine->connection() === null) {
            $this->markTestSkipped('No connection to database available');
        }
        $this->assertNull($this->Engine->pop('default'));
        $this->assertTrue($this->Engine->push($this->Fixtures->default['first'], ['queue' => 'default', 'priority' => 4]));
        sleep(1);
        $this->assertTrue($this->Engine->push($this->Fixtures->default['second'], ['queue' => 'default', 'priority' => 1]));
        sleep(1);
        $this->assertTrue($this->Engine->push($this->Fixtures->default['third'], ['queue' => 'default', 'priority' => 3]));
        $msg = 'We should have returned the first job, as it has the lowest id (FIFO)';
        $firstJobFixture = $this->Fixtures->default['first'];
        $firstJobFixture['options']['priority'] = 4;
        $this->assertEquals($firstJobFixture, $this->Engine->pop([
            'queue' => 'default',
            'pop_order' => PdoEngine::POP_ORDER_FIFO,
        ]), $msg);
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\PdoEngine::pop
     * @covers josegonzalez\Queuesadilla\Engine\MysqlEngine::pop
     * @covers josegonzalez\Queuesadilla\Engine\PostgresEngine::pop
     */
    public function testPopInvalid()
    {
        if ($this->Engine->connection() === null) {
            $this->markTestSkipped('No connection to database available');
        }
        $this->assertNull($this->Engine->pop('default'));
        $this->assertTrue($this->Engine->push($this->Fixtures->default['first'], ['queue' => 'default', 'priority' => 4]));
        $this->assertTrue($this->Engine->push($this->Fixtures->default['second'], ['queue' => 'default', 'priority' => 1]));
        $this->assertTrue($this->Engine->push($this->Fixtures->default['third'], ['queue' => 'default', 'priority' => 3]));
        $msg = 'We should have returned the second job, as it has the lowest priority';
        $secondJobFixture = $this->Fixtures->default['second'];
        $secondJobFixture['options']['priority'] = 1;
        $this->assertEquals($secondJobFixture, $this->Engine->pop('default'), $msg);
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\PdoEngine::push
     * @covers josegonzalez\Queuesadilla\Engine\MysqlEngine::push
     * @covers josegonzalez\Queuesadilla\Engine\PostgresEngine::push
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
     * @covers josegonzalez\Queuesadilla\Engine\MysqlEngine::release
     * @covers josegonzalez\Queuesadilla\Engine\PostgresEngine::release
     */
    public function testRelease()
    {
        if ($this->Engine->connection() === null) {
            $this->markTestSkipped('No connection to database available');
        }
        $this->assertFalse($this->Engine->release(null, 'default'));

        $this->Engine->push($this->Fixtures->default['first'], 'default');
        $item = $this->Engine->pop();
        $this->assertTrue($this->Engine->release($item));
        $sth = $this->execute($this->Engine->connection(), 'SELECT * FROM jobs WHERE id = ' . $this->Fixtures->default['first']['id']);
        $this->assertTrue($sth->rowCount() == 0);

        $this->assertTrue($this->Engine->push($this->Fixtures->default['second'], [
            'attempts' => 10
        ]));

        $item2 = $this->Engine->pop();
        $item2['attempts'] = 9;
        $item2['delay'] = $item2['options']['attempts_delay'];
        $this->assertTrue($this->Engine->release($item2));

        $date = new \DateTime();
        $date->modify('+10 minutes');
        $sth = $this->execute($this->Engine->connection(), 'SELECT * FROM jobs WHERE id = ' . $item2['id']);
        $results = $sth->fetch(PDO::FETCH_ASSOC);
        $inTenMinutes = $date->format('Y-m-d H:i:s');

        $this->assertEquals($inTenMinutes, $results['delay_until']);
        $this->assertEquals(9, $results['attempts']);
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\PdoEngine::queues
     * @covers josegonzalez\Queuesadilla\Engine\MysqlEngine::queues
     * @covers josegonzalez\Queuesadilla\Engine\PostgresEngine::queues
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

    /**
     * @covers josegonzalez\Queuesadilla\Engine\PdoEngine::cleanup
     * @covers josegonzalez\Queuesadilla\Engine\MysqlEngine::cleanup
     * @covers josegonzalez\Queuesadilla\Engine\PostgresEngine::cleanup
     */
    public function testCleanup()
    {
        if ($this->Engine->connection() === null) {
            $this->markTestSkipped('No connection to database available');
        }

        $this->Engine->push($this->Fixtures->default['first'], [
            'queue' => 'default',
            'expires_in' => 2
        ]);
        $pop1 = $this->Engine->pop();
        $this->assertEquals($pop1['id'], 1);

        $this->Engine->push($this->Fixtures->default['first'], [
            'queue' => 'default',
            'expires_in' => 1
        ]);
        sleep(2);
        $pop2 = $this->Engine->pop();
        $this->assertEquals($pop2, null);
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

    protected function expandFixtureData()
    {
        foreach ($this->Fixtures->default as &$default) {
            $default['options']['attempts_delay'] = 600;
        }
        foreach ($this->Fixtures->other as &$other) {
            $other['options']['attempts_delay'] = 600;
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
