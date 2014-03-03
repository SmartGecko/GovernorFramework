<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\EventHandling;

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
            $this->listeners->rewind();

            while ($this->listeners->valid()) {
                $listener = $this->listeners->current();
                $listener->handle($event);

                $this->listeners->next();
            }
        }
    }

    public function subscribe(EventListenerInterface $eventListener)
    {
        if (!$this->listeners->contains($eventListener)) {
            $this->listeners->attach($eventListener);
        }
    }

    public function unsubscribe(EventListenerInterface $eventListener)
    {
        if ($this->listeners->contains($eventListener)) {
            $this->listeners->detach($eventListener);
        }
    }

}
