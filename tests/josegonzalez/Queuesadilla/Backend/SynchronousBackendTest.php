<?php

namespace josegonzalez\Queuesadilla\Backend;

use \DateTime;
use \DateInterval;
use \josegonzalez\Queuesadilla\Backend\SynchronousBackend;
use \josegonzalez\Queuesadilla\Worker\SequentialWorker;
use \josegonzalez\Queuesadilla\Worker\TestWorker;
use \PHPUnit_Framework_TestCase;
use \ReflectionClass;

class SynchronousBackendTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->Backend = $this->getMock('josegonzalez\Queuesadilla\Backend\SynchronousBackend', array('getWorker', 'id'));
        $this->Backend->expects($this->any())
                ->method('getWorker')
                ->will($this->returnValue(new TestWorker($this->Backend)));
        $this->Backend->expects($this->any())
                ->method('id')
                ->will($this->onConsecutiveCalls('1', '2', '3', '4'));
    }

    public function tearDown()
    {
        unset($this->Backend);
    }

    /**
     * @covers josegonzalez\Queuesadilla\Backend\SynchronousBackend::push
     * @covers josegonzalez\Queuesadilla\Backend\MemoryBackend::push
     * @covers josegonzalez\Queuesadilla\Backend\MemoryBackend::pop
     */
    public function testPush()
    {
        $this->assertEquals(array(
            'id' => '1',
            'class' => null,
            'vars' => array(),
            'options' => array('queue' => 'default'),
        ), $this->Backend->push(null, array(), 'default'));
        $this->assertNull($this->Backend->push('some_function', array(), array('delay' => 30)));

        $datetime = new DateTime();
        $this->assertEquals(array(
            'id' => '3',
            'class' => 'another_function',
            'vars' => array(),
            'options' => array(
              'queue' => 'default',
              'expires_at' => $datetime->add(new DateInterval(sprintf('PT%sS', 1)))
            ),
        ), $this->Backend->push('another_function', array(), array('expires_in' => 1)));
        $this->assertEquals(array(
            'id' => '4',
            'class' => 'yet_another_function',
            'vars' => array(),
            'options' => array('queue' => 'default'),
        ), $this->Backend->push('yet_another_function', array(), 'default'));

        sleep(2);

        $this->assertNull($this->Backend->pop());
        $this->assertNull($this->Backend->pop());
        $this->assertNull($this->Backend->pop());
        $this->assertNull($this->Backend->pop());
    }

    /**
     * @covers josegonzalez\Queuesadilla\Backend\SynchronousBackend::getWorker
     */
    public function testGetWorker()
    {
        $Backend = new SynchronousBackend();
        $this->assertInstanceOf(
            '\josegonzalez\Queuesadilla\Worker\SequentialWorker',
            $this->protectedMethodCall($Backend, 'getWorker')
        );
    }

    public function protectedMethodCall(&$object, $methodName, array $parameters = array())
    {
        $reflection = new ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }
}
