<?php

namespace josegonzalez\Queuesadilla\Utility;

use \josegonzalez\Queuesadilla\Utility\DsnParserTrait;
use \InvalidArgumentException;
use \PHPUnit_Framework_TestCase;

class DsnParserTraitTest extends PHPUnit_Framework_TestCase
{
    public function testCustomParseDsn()
    {
        $subject = $this->getObjectForTrait('josegonzalez\Queuesadilla\Utility\DsnParserTrait');
        $this->assertEquals([], $subject->parseDsn(''));

        $this->assertEquals(':', $subject->parseDsn(':'));

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
     * @expectedException InvalidArgumentException
     */
    public function testCustomParseDsnThrowsException()
    {
        $subject = $this->getObjectForTrait('josegonzalez\Queuesadilla\Utility\DsnParserTrait');
        $this->assertEquals([], $subject->parseDsn(['not-empty']));
    }
}
