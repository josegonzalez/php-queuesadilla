<?php

use \PHPUnit_Framework_TestCase;

use josegonzalez\Queuesadilla\Backend\MysqlBackend;

class MysqlBackendTest extends PHPUnit_Framework_TestCase
{

    public function setUp()
    {
    	$this->config = array(
            'queue' => 'default',
            'login' => 'travis',
            'password' => '',
        );
        $this->Backend = new MysqlBackend($this->config);
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
        $Backend = new MysqlBackend($this->config);
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
        $this->assertFalse($this->Backend->delete(null));
    }

    /**
     * @covers josegonzalez\Queuesadilla\Backend\MysqlBackend::pop
     */
    public function testPop()
    {
        $this->assertNull($this->Backend->pop('default'));
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
        $this->assertFalse($this->Backend->release(null, 'default'));
    }

}
