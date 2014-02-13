<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\EventHandling;

use Governor\Framework\Domain\EventMessageInterface;

/**
 * Description of SimpleEventBus
 *
 * @author david
 */
class SimpleEventBus implements EventBusInterface
{

    /**
     *
     * @var \SplObjectStorage
     */
    private $listeners;

    function __construct()
    {
        $this->listeners = new \SplObjectStorage();
    }

    public function publish(array $events)
    {        
        foreach ($events as $event) {
            $listeners = $this->getListenersFor($event);
            foreach ($listeners as $listener) {                
                $listener->handle($event);
            }
        }
    }

    protected function getListenersFor(EventMessageInterface $eventName)
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
