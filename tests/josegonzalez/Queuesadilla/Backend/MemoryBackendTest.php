<?php

namespace josegonzalez\Queuesadilla\Backend;

use \josegonzalez\Queuesadilla\Backend\MemoryBackend;
use \PHPUnit_Framework_TestCase;

class MemoryBackendTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->Backend = new MemoryBackend();
    }

    public function tearDown()
    {
        unset($this->Backend);
    }

    /**
     * @covers josegonzalez\Queuesadilla\Backend\MemoryBackend::__construct
     * @covers josegonzalez\Queuesadilla\Backend\MemoryBackend::connected
     */
    public function testConstruct()
    {
        $Backend = new MemoryBackend();
        $this->assertTrue($Backend->connected());
    }

    /**
     * @covers josegonzalez\Queuesadilla\Backend\MemoryBackend::connect
     */
    public function testConnect()
    {
        $this->assertTrue($this->Backend->connect());
    }

    /**
     * @covers josegonzalez\Queuesadilla\Backend\MemoryBackend::delete
     */
    public function testDelete()
    {
        $Backend = $this->getMock('josegonzalez\Queuesadilla\Backend\MemoryBackend', array('id'));
        $Backend->expects($this->any())
                ->method('id')
                ->will($this->returnValue('1'));

        $this->assertFalse($Backend->delete(null));
        $this->assertFalse($Backend->delete(false));
        $this->assertFalse($Backend->delete(1));
        $this->assertFalse($Backend->delete('string'));
        $this->assertFalse($Backend->delete(array('key' => 'value')));
        $this->assertFalse($Backend->delete(array('id' => '1')));

        $this->assertTrue($Backend->push('some_function'));
        $this->assertTrue($Backend->delete(array('id' => '1')));
    }

    /**
     * @covers josegonzalez\Queuesadilla\Backend\MemoryBackend::pop
     */
    public function testPop()
    {
        $Backend = $this->getMock('josegonzalez\Queuesadilla\Backend\MemoryBackend', array('id'));
        $Backend->expects($this->any())
                ->method('id')
                ->will($this->returnValue('1'));

        $this->assertNull($Backend->pop('default'));
        $this->assertTrue($Backend->push(null, array(), 'default'));
        $this->assertEquals(array(
            'id' => '1',
            'class' => null,
            'vars' => array(),
            'options' => array('queue' => 'default'),
        ), $Backend->pop('default'));
    }

    /**
     * @covers josegonzalez\Queuesadilla\Backend\MemoryBackend::push
     * @covers josegonzalez\Queuesadilla\Backend\MemoryBackend::pop
     */
    public function testPush()
    {
        $this->assertTrue($this->Backend->push(null, array(), 'default'));
        $this->assertTrue($this->Backend->push('some_function', array(), array(
            'delay' => 30,
        )));
        $this->assertTrue($this->Backend->push('another_function', array(), array(
            'expires_in' => 1,
        )));
        $this->assertTrue($this->Backend->push('yet_another_function', array(), 'default'));

        sleep(2);

        $pop1 = $this->Backend->pop();
        $pop2 = $this->Backend->pop();
        $pop3 = $this->Backend->pop();
        $pop4 = $this->Backend->pop();

        $this->assertNotEmpty($pop1['id']);
        $this->assertNull($pop1['class']);
        $this->assertEmpty($pop1['vars']);
        $this->assertEquals('yet_another_function', $pop2['class']);
        $this->assertNull($pop3);
        $this->assertNull($pop4);
    }

    /**
     * @covers josegonzalez\Queuesadilla\Backend\MemoryBackend::release
     */
    public function testRelease()
    {
        $this->assertFalse($this->Backend->release(null, 'default'));
    }
}
