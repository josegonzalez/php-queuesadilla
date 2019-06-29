<?php

namespace josegonzalez\Queuesadilla\Utility;

use InvalidArgumentException;
use josegonzalez\Queuesadilla\TestCase;
use josegonzalez\Queuesadilla\Utility\DsnParserTrait;

class DsnParserTraitTest extends TestCase
{
    /**
     * @covers josegonzalez\Queuesadilla\Utility\DsnParserTrait::parseDsn
     */
    public function testParseDsn()
    {
        $subject = $this->getObjectForTrait('josegonzalez\Queuesadilla\Utility\DsnParserTrait');
        $this->assertEquals([], $subject->parseDsn(''));

        $this->assertEquals([], $subject->parseDsn(':'));

        $dsn = 'test://user:pass@host:1';
        $expected = [
            'host' => 'host',
            'port' => 1,
            'scheme' => 'test',
            'user' => 'user',
            'pass' => 'pass'
        ];
        $this->assertEquals($expected, $subject->parseDsn($dsn));

        $dsn = 'mysql://localhost:3306/database';
        $expected = [
            'host' => 'localhost',
            'database' => 'database',
            'port' => 3306,
            'scheme' => 'mysql',
        ];
        $this->assertEquals($expected, $subject->parseDsn($dsn));

        $dsn = 'mysql://user:password@localhost:3306/database';
        $expected = [
            'host' => 'localhost',
            'pass' => 'password',
            'database' => 'database',
            'port' => 3306,
            'scheme' => 'mysql',
            'user' => 'user',
        ];
        $this->assertEquals($expected, $subject->parseDsn($dsn));

        $dsn = 'sqlite:///memory:';
        $expected = [
            'database' => 'memory:',
            'scheme' => 'sqlite',
        ];
        $this->assertEquals($expected, $subject->parseDsn($dsn));

        $dsn = 'sqlite:///?database=memory:';
        $expected = [
            'database' => 'memory:',
            'scheme' => 'sqlite',
        ];
        $this->assertEquals($expected, $subject->parseDsn($dsn));

        $dsn = 'sqlserver://sa:Password12!@.\SQL2012SP1/cakephp?MultipleActiveResultSets=false';
        $expected = [
            'host' => '.\SQL2012SP1',
            'MultipleActiveResultSets' => false,
            'pass' => 'Password12!',
            'database' => 'cakephp',
            'scheme' => 'sqlserver',
            'user' => 'sa',
        ];
        $this->assertEquals($expected, $subject->parseDsn($dsn));
    }

    /**
     * @covers josegonzalez\Queuesadilla\Utility\DsnParserTrait::parseDsn
     */
    public function testParseDsnThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);
        $subject = $this->getObjectForTrait('josegonzalez\Queuesadilla\Utility\DsnParserTrait');
        $this->assertEquals([], $subject->parseDsn(['not-empty']));
    }
}
