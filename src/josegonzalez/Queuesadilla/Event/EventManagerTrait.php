<?php

namespace josegonzalez\Queuesadilla\Event;

use InvalidArgumentException;
use josegonzalez\Queuesadilla\Event\EventListenerInterface;
use League\Event\Emitter as EventManager;
use League\Event\EmitterInterface;

trait EventManagerTrait
{

    /**
     * Instance of the League\Event\EmitterInterface this object is using
     * to dispatch inner events.
     *
     * @var \League\Event\EmitterInterface
     */
    protected $eventManager = null;

    /**
     * Default class name for new event objects.
     *
     * @var string
     */
    protected $eventClass = '\josegonzalez\Queuesadilla\Event\Event';

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
            $this->eventManager = new EventManager();
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
     * @return \League\Event\AbstractEvent
     */
    public function dispatchEvent($name, $data = null, $subject = null)
    {
        if ($subject === null) {
            $subject = $this;
        }

        $event = new $this->eventClass($name, $subject, $data);
        $this->eventManager()->emit($event);

        return $event;
    }

    public function attachListener($name, $listener = null, array $options = [])
    {
        if (!$listener) {
            if ($name instanceof MultiEventListener) {
                foreach ($name->implementedEvents() as $event => $method) {
                    $method;
                    $this->attachListener($event, $name, $options);
                }

                return;
            }
            throw new \InvalidArgumentException('Invalid listener for event');
        }
        $options += ['priority' => 0];
        $this->eventManager()->addListener($name, $listener, $options['priority']);
    }
}
