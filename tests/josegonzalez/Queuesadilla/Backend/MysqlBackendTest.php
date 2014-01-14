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
        $this->Backend->drop();
    }

    public function tearDown()
    {
        $this->Backend->drop();
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
     * @covers josegonzalez\Queuesadilla\Backend\MysqlBackend::__construct
     * @covers josegonzalez\Queuesadilla\Backend\MysqlBackend::connected
     * @expectedException PDOException
     */
    public function testConstructException()
    {
        $config = $this->config;
        $config['unix_socket'] = '/tmp/missing/mysql.sock';
        $SocketBackend = new MysqlBackend($config);
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
        $this->assertFalse($this->Backend->delete(false));
        $this->assertFalse($this->Backend->delete(1));
        $this->assertFalse($this->Backend->delete('string'));
        $this->assertFalse($this->Backend->delete(array('key' => 'value')));
        $this->assertFalse($this->Backend->delete(array('id' => '1')));

        $this->assertTrue($this->Backend->push('some_function'));
        $this->assertTrue($this->Backend->delete(array('id' => '1')));
    }

    /**
     * @covers josegonzalez\Queuesadilla\Backend\MysqlBackend::pop
     */
    public function testPop()
    {
        $this->assertNull($this->Backend->pop('default'));
        $this->assertTrue($this->Backend->push(null, array(), 'default'));
        $this->assertEquals(array(
            'id' => '1',
            'class' => null,
            'vars' => array()
        ), $this->Backend->pop('default'));
    }

    /**
     * @covers josegonzalez\Queuesadilla\Backend\MysqlBackend::push
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
     * @covers josegonzalez\Queuesadilla\Backend\MysqlBackend::release
     */
    public function testRelease()
    {
        $this->assertFalse($this->Backend->release(null, 'default'));
    }

    /**
     * @covers josegonzalez\Queuesadilla\Backend\MysqlBackend::execute
     * @expectedException PDOException
     */
    public function testExecutePdoException()
    {
        $this->assertTrue($this->Backend->push(null, array(), 'default'));
        $this->Backend->execute('derp');
    }

}
