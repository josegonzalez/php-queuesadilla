<?php

use \PHPUnit_Framework_TestCase;

use josegonzalez\Queuesadilla\Backend\TestBackend;

class MysqlBackendTest extends PHPUnit_Framework_TestCase
{

    public function setUp()
    {
        $this->Backend = new MysqlBackend(array(
            'queue' => 'default',
        ));
    }

    public function tearDown()
    {
        unset($this->Backend);
    }

    /**
     * @covers josegonzalez\Queuesadilla\Backend\MysqlBackend::__construct
     * @covers josegonzalez\Queuesadilla\Backend\MysqlBackend::connected
     */
    public function testConstruct()
    {
        $Backend = new MysqlBackend;
        $this->assertTrue($Backend->connected());
    }

    /**
     * @covers josegonzalez\Queuesadilla\Backend\MysqlBackend::connect
     */
    public function testConnect()
    {
        $this->assertTrue($this->Backend->connect());
    }

    /**
     * @covers josegonzalez\Queuesadilla\Backend\MysqlBackend::delete
     */
    public function testDelete()
    {
        $this->assertTrue($this->Backend->delete(null));
    }

    /**
     * @covers josegonzalez\Queuesadilla\Backend\MysqlBackend::pop
     */
    public function testPop()
    {
        $this->assertTrue($this->Backend->pop('default'));

        $this->Backend->return = false;
        $this->assertFalse($this->Backend->pop('default'));
    }

    /**
     * @covers josegonzalez\Queuesadilla\Backend\MysqlBackend::push
     */
    public function testPush()
    {
        $this->assertTrue($this->Backend->push(null, array(), 'default'));
    }

    /**
     * @covers josegonzalez\Queuesadilla\Backend\MysqlBackend::release
     */
    public function testRelease()
    {
        $this->assertTrue($this->Backend->release(null, 'default'));
    }

}
