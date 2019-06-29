<?php
namespace josegonzalez\Queuesadilla\Engine;

use josegonzalez\Queuesadilla\Engine\AbstractPdoEngineTest;
use josegonzalez\Queuesadilla\FixtureData;
use josegonzalez\Queuesadilla\TestCase;
use PDO;
use PDOException;

class MysqlEngineTest extends AbstractPdoEngineTest
{
    public function setUp() : void
    {
        $this->url = getenv('MYSQL_URL');
        $this->engineClass = 'josegonzalez\Queuesadilla\Engine\MysqlEngine';
        parent::setUp();
    }

    protected function clearEngine()
    {
        $this->execute($this->Engine->connection(), 'TRUNCATE TABLE jobs');
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\PdoEngine::quoteIdentifier
     * @covers josegonzalez\Queuesadilla\Engine\MysqlEngine::quoteIdentifier
     */
    public function testQuoteIdentifier()
    {
        $this->assertEquals('*', $this->Engine->quoteIdentifier('*'));
        $this->assertEquals('', $this->Engine->quoteIdentifier(''));
        $this->assertEquals('`my_field`', $this->Engine->quoteIdentifier('my_field'));
        $this->assertEquals('`my_table`.`my_field`', $this->Engine->quoteIdentifier('my_table.my_field'));
        $this->assertEquals('`my_table`.*', $this->Engine->quoteIdentifier('my_table.*'));
        $this->assertEquals('`my_field`', $this->Engine->quoteIdentifier('`my_field`'));
    }
}
