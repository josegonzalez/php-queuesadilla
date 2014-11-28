<?php

namespace josegonzalez\Queuesadilla\Utility;

use \josegonzalez\Queuesadilla\Utility\LoggerTraitTest;
use \Monolog\Logger;
use \PHPUnit_Framework_TestCase;
use \Psr\Log\NullLogger;

class LoggerTraitTest extends PHPUnit_Framework_TestCase
{
    /**
     * @covers josegonzalez\Queuesadilla\Utility\LoggerTrait::setLogger
     */
    public function testSetLogger()
    {
        $subject = $this->getObjectForTrait('josegonzalez\Queuesadilla\Utility\LoggerTrait');
        $this->assertInstanceOf('Psr\Log\NullLogger', $subject->setLogger(null));

        $logger = new Logger('test');
        $subject = $this->getObjectForTrait('josegonzalez\Queuesadilla\Utility\LoggerTrait');
        $this->assertEquals($logger, $subject->setLogger($logger));
        $this->assertInstanceOf('Monolog\Logger', $subject->setLogger($logger));

        $logger = new NullLogger;
        $subject = $this->getObjectForTrait('josegonzalez\Queuesadilla\Utility\LoggerTrait');
        $this->assertEquals($logger, $subject->setLogger($logger));
        $this->assertInstanceOf('Psr\Log\NullLogger', $subject->setLogger($logger));
    }

    /**
     * @covers josegonzalez\Queuesadilla\Utility\LoggerTrait::logger
     */
    public function testLogger()
    {
        $subject = $this->getObjectForTrait('josegonzalez\Queuesadilla\Utility\LoggerTrait');
        $this->assertNull($subject->logger());
    }
}
