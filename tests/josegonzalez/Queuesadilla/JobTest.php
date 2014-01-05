<?php

use \PHPUnit_Framework_TestCase;

use josegonzalez\Queuesadilla\Job;
use josegonzalez\Queuesadilla\Backend\TestBackend;

class JobTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $config = array();
        $items = array(
            array(
                'delay' => 0,
                'class' => 'Foo',
                'vars' => array(
                    'foo' => 'bar',
                    'baz' => 'qux',
                ),
            ),
            array(
                'attempts' => 0,
                'delay' => 0,
                'class' => 'Foo',
                'vars' => array(
                    'foo' => 'bar',
                    'baz' => 'qux',
                ),
            ),
            array(
                'attempts' => 1,
                'delay' => 0,
                'class' => 'Foo',
                'vars' => array(
                    'foo' => 'bar',
                    'baz' => 'qux',
                ),
            ),
        );

        $this->Backend = new TestBackend($config);
        $this->Jobs = array(
            new Job($items[0], $this->Backend),
            new Job($items[1], $this->Backend),
            new Job($items[2], $this->Backend),
        );
    }

    public function tearDown()
    {
        unset($this->Backend);
        unset($this->Jobs);
    }

    public function testAttempts()
    {
        $this->assertEquals(0, $this->Jobs[0]->attempts());
        $this->assertEquals(1, $this->Jobs[1]->attempts());
        $this->assertEquals(1, $this->Jobs[2]->attempts());
    }

    public function testData()
    {
        $this->assertNull($this->Jobs[0]->data('unset_variable'));
        $this->assertTrue($this->Jobs[0]->data('unset_variable', true));
        $this->assertEquals('bar', $this->Jobs[0]->data('foo'));
        $this->assertEquals('qux', $this->Jobs[0]->data('baz'));
    }

    public function testItem()
    {
        $this->assertEquals(array(
            'delay' => 0,
            'class' => 'Foo',
            'vars' => array(
                'foo' => 'bar',
                'baz' => 'qux',
            ),
        ), $this->Jobs[0]->item());

        $this->assertEquals(array(
            'attempts' => 0,
            'delay' => 0,
            'class' => 'Foo',
            'vars' => array(
                'foo' => 'bar',
                'baz' => 'qux',
            ),
        ), $this->Jobs[0]->item());

        $this->assertEquals(array(
            'attempts' => 1,
            'delay' => 0,
            'class' => 'Foo',
            'vars' => array(
                'foo' => 'bar',
                'baz' => 'qux',
            ),
        ), $this->Jobs[0]->item());       
    }
}