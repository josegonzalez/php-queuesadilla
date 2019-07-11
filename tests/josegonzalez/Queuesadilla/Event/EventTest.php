<?php

namespace josegonzalez\Queuesadilla;

use josegonzalez\Queuesadilla\Event\Event;
use stdClass;

class EventTest extends TestCase
{
    public function setUp() : void
    {
        $this->object = new stdClass();
        $this->Event = new Event('test', $this->object, []);
    }

    public function tearDown() : void
    {
        unset($this->object);
        unset($this->Event);
    }

    /**
     * @covers josegonzalez\Queuesadilla\Event\Event::__construct
     */
    public function testConstruct()
    {
        $this->assertInstanceOf('\josegonzalez\Queuesadilla\Event\Event', $this->Event);
        $this->assertInstanceOf('\josegonzalez\Queuesadilla\Event\Event', new Event('name'));
        $this->assertInstanceOf('\josegonzalez\Queuesadilla\Event\Event', new Event('name', $this->object));
        $this->assertInstanceOf('\josegonzalez\Queuesadilla\Event\Event', new Event('name', $this->object, []));
        $this->assertInstanceOf('\josegonzalez\Queuesadilla\Event\Event', new Event('name', $this->object, null));
    }

    public function testGetter()
    {
        $this->assertEquals('test', $this->Event->name);
        $this->assertEquals($this->object, $this->Event->subject);
        $this->assertNull($this->Event->invalidAttribute);
    }

    /**
     * @covers josegonzalez\Queuesadilla\Event\Event::__get
     * @covers josegonzalez\Queuesadilla\Event\Event::getName
     * @covers josegonzalez\Queuesadilla\Event\Event::name
     */
    public function testGetName()
    {
        $this->assertEquals('test', $this->Event->name);
        $this->assertEquals('test', $this->Event->getName());
        $this->assertEquals('test', $this->Event->name());
    }

    /**
     * @covers josegonzalez\Queuesadilla\Event\Event::__get
     * @covers josegonzalez\Queuesadilla\Event\Event::subject
     */
    public function testGetSubject()
    {
        $this->assertEquals($this->object, $this->Event->subject);
        $this->assertEquals($this->object, $this->Event->subject());
    }

    /**
     * @covers josegonzalez\Queuesadilla\Event\Event::isStopped
     */
    public function testIsStopped()
    {
        $this->assertFalse($this->Event->isStopped());
        $this->Event->stopPropagation();
        $this->assertTrue($this->Event->isStopped());
    }

    /**
     * @covers josegonzalez\Queuesadilla\Event\Event::data
     */
    public function testData()
    {
        $this->assertIsArray($this->Event->data());
        $this->assertIsArray($this->Event->data);
        $this->assertEquals([], $this->Event->data);

        $this->Event->data = ['test' => 'passed'];
        $this->assertEquals(['test' => 'passed'], $this->Event->data);

        $Event = new Event('test');
        $this->assertIsArray($Event->data());
        $this->assertNull($Event->data);

        $Event->data = ['test' => 'passed'];
        $this->assertIsArray($Event->data());
        $this->assertIsArray($Event->data);
        $this->assertEquals(['test' => 'passed'], $Event->data);
    }
}
