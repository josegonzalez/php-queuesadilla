<?php

namespace josegonzalez\Queuesadilla\Event;

/**
 * Objects implementing this interface should declare the `implementedEvents` function
 * to notify the event manager what methods should be called when an event is triggered.
 *
 */
interface EventListenerInterface
{

    /**
     * Returns a list of events this object is implementing. When the class is registered
     * in an event manager, each individual method will be associated with the respective event.
     *
     * ### Example:
     *
     * {{{
     *  public function implementedEvents() {
     *      return array(
     *          'Order.complete' => 'sendEmail',
     *          'Article.afterBuy' => 'decrementInventory',
     *          'User.onRegister' => array('callable' => 'logRegistration', 'priority' => 20, 'passParams' => true)
     *      );
     *  }
     * }}}
     *
     * @return array associative array or event key names pointing to the function
     * that should be called in the object when the respective event is fired
     */
    public function implementedEvents();
}
