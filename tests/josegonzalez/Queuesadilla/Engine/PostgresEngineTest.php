<?php
namespace josegonzalez\Queuesadilla\Engine;

use josegonzalez\Queuesadilla\Engine\AbstractPdoEngineTest;
use josegonzalez\Queuesadilla\FixtureData;
use josegonzalez\Queuesadilla\TestCase;
use PDO;
use PDOException;

class PostgresEngineTest extends AbstractPdoEngineTest
{
    public function setUp() : void
    {
        $this->url = getenv('POSTGRES_URL');
        $this->engineClass = 'josegonzalez\Queuesadilla\Engine\PostgresEngine';
        parent::setUp();
    }

    protected function clearEngine()
    {
        $this->execute($this->Engine->connection(), 'TRUNCATE TABLE jobs');
        $this->execute($this->Engine->connection(), 'ALTER SEQUENCE jobs_id_seq RESTART WITH 1');
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\PdoEngine::quoteIdentifier
     * @covers josegonzalez\Queuesadilla\Engine\PostgresEngine::quoteIdentifier
     */
    public function testQuoteIdentifier()
    {
        $this->assertEquals('*', $this->Engine->quoteIdentifier('*'));
        $this->assertEquals('', $this->Engine->quoteIdentifier(''));
        $this->assertEquals('"my_field"', $this->Engine->quoteIdentifier('my_field'));
        $this->assertEquals('"my_table"."my_field"', $this->Engine->quoteIdentifier('my_table.my_field'));
        $this->assertEquals('"my_table".*', $this->Engine->quoteIdentifier('my_table.*'));
        $this->assertEquals('"my_field"', $this->Engine->quoteIdentifier('"my_field"'));
    }
}
