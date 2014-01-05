<?php

use \PHPUnit_Framework_TestCase;

use josegonzalez\Queuesadilla\Backend\TestBackend;

class TestBackendTest extends PHPUnit_Framework_TestCase
{

    public function setUp()
    {
        $this->Backend = new TestBackend(array(
            'queue' => 'default',
        ));
    }

    public function tearDown()
    {
        unset($this->Backend);
    }

    /**
     * @covers josegonzalez\Queuesadilla\Backend\TestBackend::connect
     */
    public function testConnect()
    {
        $this->assertTrue($this->Backend->connect());

        $this->Backend->return = false;
        $this->assertTrue($this->Backend->connect());
    }

    /**
     * @covers josegonzalez\Queuesadilla\Backend\TestBackend::delete
     */
    public function testDelete()
    {
        $this->assertTrue($this->Backend->delete(null));

        $this->Backend->return = false;
        $this->assertTrue($this->Backend->delete(null));
    }

    /**
     * @covers josegonzalez\Queuesadilla\Backend\TestBackend::pop
     */
    public function testPop()
    {
        $this->assertTrue($this->Backend->pop('default'));

        $this->Backend->return = false;
        $this->assertTrue($this->Backend->pop('default'));
    }

    /**
     * @covers josegonzalez\Queuesadilla\Backend\TestBackend::push
     */
    public function testPush()
    {
        $this->assertTrue($this->Backend->push(null, array(), 'default'));

        $this->Backend->return = false;
        $this->assertTrue($this->Backend->connect(null, array(), 'default'));
    }

    /**
     * @covers josegonzalez\Queuesadilla\Backend\TestBackend::release
     */
    public function testRelease()
    {
        $this->assertTrue($this->Backend->release(null, 'default'));

        $this->Backend->return = false;
        $this->assertTrue($this->Backend->release(null, 'default'));
    }

}
