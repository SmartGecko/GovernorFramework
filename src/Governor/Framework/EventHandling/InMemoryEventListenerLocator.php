<?php

namespace Governor\Framework\EventHandling;

use Governor\Framework\Domain\EventMessageInterface;

/**
 * In Memory Event Handler Locator
 *
 * You can register Event handlers and every method starting
 * with "on" will be registered as handling an event.
 *
 * By convention the part after the "on" matches the event name.
 * Comparisons are done in lower-case.
 */
class InMemoryEventListenerLocator implements EventListenerLocatorInterface
{

    private $listeners;

    public function __construct()
    {
        $this->listeners = new \SplObjectStorage();
    }

    public function getListenersFor(EventMessageInterface $eventName)
    {
        $result = array();
        $this->listeners->rewind();

        while ($this->listeners->valid()) {
            if ($eventName->getPayloadType() === $this->listeners->getInfo()) {
                $result[] = $this->listeners->current();
            }

            $this->listeners->next();
        }
        return $result;
    }

    public function subscribe($eventName, EventListenerInterface $eventListener)
    {
        if (!$this->listeners->contains($eventListener)) {
            $this->listeners->attach($eventListener, $eventName);
        }
    }

    public function unsubscribe($eventName,
            EventListenerInterface $eventListener)
    {
        if ($this->listeners->contains($eventListener)) {
            $this->listeners->detach($eventListener);
        }
    }

}
