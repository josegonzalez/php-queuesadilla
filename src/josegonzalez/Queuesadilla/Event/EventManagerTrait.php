<?php

namespace josegonzalez\Queuesadilla\Event;

use josegonzalez\Queuesadilla\Event\Event;
use League\Event\PriorityEmitter;
use League\Event\EmitterInterface;

trait EventManagerTrait
{

/**
 * Instance of the League\Event\PriorityEmitter this object is using
 * to dispatch inner events.
 *
 * @var \League\Event\PriorityEmitter
 */
    protected $eventManager = null;

/**
 * Returns the League\Event\EmitterInterface manager instance for this object.
 *
 * You can use this instance to register any new listeners or callbacks to the
 * object events, or create your own events and trigger them at will.
 *
 * @param \League\Event\EmitterInterface $eventManager the eventManager to set
 * @return \League\Event\EmitterInterface
 */
    public function eventManager(EmitterInterface $eventManager = null)
    {
        if ($eventManager !== null) {
            $this->eventManager = $eventManager;
        } elseif (empty($this->eventManager)) {
            $this->eventManager = new PriorityEmitter();
        }
        return $this->eventManager;
    }

/**
 * Wrapper for creating and dispatching events.
 *
 * Returns a dispatched event.
 *
 * @param string $name Name of the event.
 * @param array $data Any value you wish to be transported with this event to
 * it can be read by listeners.
 *
 * @param object $subject The object that this event applies to
 * ($this by default).
 *
 * @return \josegonzalez\Queuesadilla\Event\Event
 */
    public function dispatchEvent($name, $data = null, $subject = null)
    {
        if ($subject === null) {
            $subject = $this;
        }

        $event = new Event($name, $subject, $data);
        $this->eventManager()->emit($event);

        return $event;
    }
}
