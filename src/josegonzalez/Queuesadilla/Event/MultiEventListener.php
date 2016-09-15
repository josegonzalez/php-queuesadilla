<?php

namespace josegonzalez\Queuesadilla\Event;

use League\Event\AbstractListener;
use League\Event\EventInterface;

abstract class MultiEventListener extends AbstractListener implements EventListenerInterface
{
    abstract public function implementedEvents();

    public function handle(EventInterface $event)
    {
        $events = $this->implementedEvents();
        if (empty($events)) {
            return;
        }
        if (!isset($events[$event->getName()])) {
            return;
        }

        $handler = $events[$event->getName()];
        if (!method_exists($this, $handler)) {
            return;
        }

        return $this->$handler($event);
    }
}
