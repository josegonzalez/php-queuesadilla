<?php

namespace josegonzalez\Queuesadilla\Event;

use League\Event\AbstractEvent;

class Event extends AbstractEvent
{
/**
 * Name of the event
 *
 * @var string
 */
    public $name = null;

/**
 * The object this event applies to (usually the same object that generates the event)
 *
 * @var object
 */
    public $subject;

/**
 * Custom data for the method that receives the event
 *
 * @var mixed
 */
    public $data = null;

    public function __construct($name, $subject = null, $data = null)
    {
        $this->name = $name;
        $this->data = $data;
        $this->subject = $subject;
    }
}
