<?php

use \PHPUnit_Framework_TestCase;

use \josegonzalez\Queuesadilla\Backend\RedisBackend;

class RedisBackendTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        if (!class_exists('Redis')) {
            $this->markTestSkipped('Redis is not installed or configured properly.');
        }

        $this->config = array(
            'queue' => 'default',
            'login' => 'travis',
            'password' => '',
        );
        $this->Backend = $this->getMock('josegonzalez\Queuesadilla\Backend\RedisBackend', array('id'), array($this->config));
        $this->Backend->expects($this->any())
                ->method('id')
                ->will($this->returnValue('1'));

        $this->Backend->connection->flushdb();
    }

    public function tearDown()
    {
        $this->Backend->connection->flushdb();
        unset($this->Backend);
    }

    /**
     * @covers josegonzalez\Queuesadilla\Backend\RedisBackend::__construct
     * @covers josegonzalez\Queuesadilla\Backend\RedisBackend::connected
     */
    public function testConstruct()
    {
        $Backend = new RedisBackend($this->config);
        $this->assertTrue($Backend->connected());
    }

    /**
     * @covers josegonzalez\Queuesadilla\Backend\RedisBackend::connect
     */
    public function testConnect()
    {
        $this->assertTrue($this->Backend->connect());
    }

    /**
     * @covers josegonzalez\Queuesadilla\Backend\RedisBackend::delete
     */
    public function testDelete()
    {
        $this->assertFalse($this->Backend->delete(null));
        $this->assertFalse($this->Backend->delete(false));
        $this->assertFalse($this->Backend->delete(1));
        $this->assertFalse($this->Backend->delete('string'));
        $this->assertFalse($this->Backend->delete(array('key' => 'value')));
        $this->assertTrue($this->Backend->delete(array('id' => '1')));

        $this->assertEquals(1, $this->Backend->push('some_function'));
        $this->assertTrue($this->Backend->delete(array('id' => '1')));
    }

    /**
     * @covers josegonzalez\Queuesadilla\Backend\RedisBackend::pop
     */
    public function testPop()
    {
        $this->assertNull($this->Backend->pop('default'));
        $this->assertEquals(1, $this->Backend->push(null, array(), 'default'));
        $this->assertEquals(array(
            'id' => '1',
            'class' => null,
            'vars' => array()
        ), $this->Backend->pop('default'));
    }

    /**
     * @covers josegonzalez\Queuesadilla\Backend\RedisBackend::push
     */
    public function testPush()
    {
        $this->assertEquals(1, $this->Backend->push(null, array(), 'default'));
        $this->assertEquals(2, $this->Backend->push('some_function', array(), array(
            'delay' => 30,
        )));
        $this->assertEquals(3, $this->Backend->push('another_function', array(), array(
            'expires_in' => 1,
        )));
        $this->assertEquals(4, $this->Backend->push('yet_another_function', array(), 'default'));

        sleep(2);

        $pop1 = $this->Backend->pop();
        $pop2 = $this->Backend->pop();
        $pop3 = $this->Backend->pop();
        $pop4 = $this->Backend->pop();

        $this->assertNull($pop1['class']);
        $this->assertEmpty($pop1['vars']);

        $this->markTestIncomplete(
          'RedisBackend does not yet implement delay or expires_in (tbd sorted sets)'
        );

        $this->assertEquals('yet_another_function', $pop2['class']);
        $this->assertNull($pop3);
        $this->assertNull($pop4);
    }

    /**
     * @covers josegonzalez\Queuesadilla\Backend\RedisBackend::pop
     * @covers josegonzalez\Queuesadilla\Backend\RedisBackend::release
     */
    public function testRelease()
    {
        $this->assertEquals(1, $this->Backend->push(null, array(), 'default'));
        $this->assertEquals(array(
            'id' => '1',
            'class' => null,
            'vars' => array()
        ), $this->Backend->pop('default'));

        $this->assertFalse($this->Backend->release(null, 'default'));

        $this->assertEquals(1, $this->Backend->release(array(
            'id' => '2',
            'class' => 'some_function',
            'vars' => array()
        ), 'default'));
        $this->assertEquals(array(
            'id' => '2',
            'class' => 'some_function',
            'vars' => array()
        ), $this->Backend->pop('default'));
    }
}
