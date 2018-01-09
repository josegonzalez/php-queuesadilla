<?php

namespace josegonzalez\Queuesadilla\Event;

use League\Event\AbstractEvent;

/**
 * Represents the transport class of events across the system. It receives a name, subject and an optional
 * payload. The name can be any string that uniquely identifies the event across the application, while the subject
 * represents the object that the event applies to.
 *
 */
class Event extends AbstractEvent
{
    /**
     * Name of the event
     *
     * @var string
     */
    protected $name = null;

    /**
     * The object this event applies to (usually the same object that generates the event)
     *
     * @var object
     */
    protected $subject;

    /**
     * Custom data for the method that receives the event
     *
     * @var mixed
     */
    public $data = null;

    /**
     * Property used to retain the result value of the event listeners
     *
     * @var mixed
     */
    public $result = null;

    public function __construct($name, $subject = null, $data = null)
    {
        $this->name = $name;
        $this->data = $data;
        $this->subject = $subject;

        return $this;
    }

    /**
     * Dynamically returns the name and subject if accessed directly
     *
     * @param string $attribute Attribute name.
     * @return mixed
     */
    public function __get($attribute)
    {
        if ($attribute === 'name' || $attribute === 'subject') {
            return $this->{$attribute}();
        }
    }

    /**
     * Returns the name of this event. This is usually used as the event identifier
     *
     * @return string
     */
    public function getName()
    {
        return $this->name();
    }

    /**
     * Returns the name of this event. This is usually used as the event identifier
     *
     * @return string
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * Returns the subject of this event
     *
     * @return string
     */
    public function subject()
    {
        return $this->subject;
    }

    /**
     * Check if the event is stopped
     *
     * @return bool True if the event is stopped
     */
    public function isStopped()
    {
        return $this->isPropagationStopped();
    }

    /**
     * Access the event data/payload.
     *
     * @return array
     */
    public function data()
    {
        return (array)$this->data;
    }
}
