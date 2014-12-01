<?php

namespace josegonzalez\Queuesadilla\Event;

use League\Event\AbstractEvent;
use League\Event\AbstractListener;

abstract class MultiEventListener extends AbstractListener implements EventListenerInterface
{
    abstract public function implementedEvents();

    public function handle(AbstractEvent $event)
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
