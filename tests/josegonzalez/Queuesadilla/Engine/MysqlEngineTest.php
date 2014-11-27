<?php

namespace josegonzalez\Queuesadilla\Engine;

use \josegonzalez\Queuesadilla\Engine\MysqlEngine;
use \PHPUnit_Framework_TestCase;

class MysqlEngineTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->config = array(
            'queue' => 'default',
            'user' => 'travis',
            'pass' => '',
        );
        $this->Engine = new MysqlEngine($this->config);
        $this->Engine->execute('TRUNCATE TABLE jobs');
    }

    public function tearDown()
    {
        $this->Engine->execute('TRUNCATE TABLE jobs');
        unset($this->Engine);
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\MysqlEngine::__construct
     * @covers josegonzalez\Queuesadilla\Engine\MysqlEngine::connected
     */
    public function testConstruct()
    {
        $Engine = new MysqlEngine($this->config);
        $this->assertTrue($Engine->connected());
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\MysqlEngine::__construct
     * @covers josegonzalez\Queuesadilla\Engine\MysqlEngine::connected
     * @expectedException PDOException
     */
    public function testConstructException()
    {
        $config = $this->config;
        $config['unix_socket'] = '/tmp/missing/mysql.sock';
        $SocketEngine = new MysqlEngine($config);
        $SocketEngine;
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\MysqlEngine::connect
     */
    public function testConnect()
    {
        $this->assertTrue($this->Engine->connect());
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\MysqlEngine::delete
     */
    public function testDelete()
    {
        $this->assertFalse($this->Engine->delete(null));
        $this->assertFalse($this->Engine->delete(false));
        $this->assertFalse($this->Engine->delete(1));
        $this->assertFalse($this->Engine->delete('string'));
        $this->assertFalse($this->Engine->delete(array('key' => 'value')));
        $this->assertFalse($this->Engine->delete(array('id' => '1')));

        $this->assertTrue($this->Engine->push('some_function'));
        $this->assertTrue($this->Engine->delete(array('id' => '1')));
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\MysqlEngine::pop
     */
    public function testPop()
    {
        $this->assertNull($this->Engine->pop('default'));
        $this->assertTrue($this->Engine->push(null, array(), 'default'));
        $this->assertEquals(array(
            'id' => '1',
            'class' => null,
            'vars' => array()
        ), $this->Engine->pop('default'));
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\MysqlEngine::push
     */
    public function testPush()
    {
        $this->assertTrue($this->Engine->push(null, array(), 'default'));
        $this->assertTrue($this->Engine->push('some_function', array(), array(
            'delay' => 30,
        )));
        $this->assertTrue($this->Engine->push('another_function', array(), array(
            'expires_in' => 1,
        )));
        $this->assertTrue($this->Engine->push('yet_another_function', array(), 'default'));

        sleep(2);

        $pop1 = $this->Engine->pop();
        $pop2 = $this->Engine->pop();
        $pop3 = $this->Engine->pop();
        $pop4 = $this->Engine->pop();

        $this->assertNotEmpty($pop1['id']);
        $this->assertNull($pop1['class']);
        $this->assertEmpty($pop1['vars']);
        $this->assertEquals('yet_another_function', $pop2['class']);
        $this->assertNull($pop3);
        $this->assertNull($pop4);
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\MysqlEngine::release
     */
    public function testRelease()
    {
        $this->assertFalse($this->Engine->release(null, 'default'));
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\MysqlEngine::execute
     * @expectedException PDOException
     */
    public function testExecutePdoException()
    {
        $this->assertTrue($this->Engine->push(null, array(), 'default'));
        $this->Engine->execute('derp');
    }
}