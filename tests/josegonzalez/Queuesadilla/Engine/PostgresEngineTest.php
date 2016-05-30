<?php
namespace josegonzalez\Queuesadilla\Engine;

use josegonzalez\Queuesadilla\Engine\AbstractPdoEngineTest;
use josegonzalez\Queuesadilla\FixtureData;
use josegonzalez\Queuesadilla\TestCase;
use PDO;
use PDOException;

class PostgresEngineTest extends AbstractPdoEngineTest
{
    public function setUp()
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
}
