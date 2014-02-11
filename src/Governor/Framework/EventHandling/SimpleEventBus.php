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

    private $listeners = array();

    public function publish(array $events)
    {
        if (empty($this->listeners)) {
            return;
        }

        foreach ($events as $event) {
            foreach ($this->listeners as $listener) {
                /*    if (logger.isDebugEnabled()) {
                  logger.debug("Dispatching Event [{}] to EventListener [{}]",
                  event.getPayloadType().getSimpleName(),
                  listener instanceof EventListenerProxy
                  ? ((EventListenerProxy) listener).getTargetType().getClass()
                  .getSimpleName()
                  : listener.getClass().getSimpleName());
                  } */
                $listener->handle($event);
            }
        }
    }

    public function subscribe(EventListenerInterface $eventListener)
    {
        $listenerType = $this->getActualListenerType($eventListener);
        $this->listeners[] = $eventListener;
    }

    public function unsubscribe(EventListenerInterface $eventListener)
    {
        $listenerType = $this->getActualListenerType($eventListener);

        if (isset($this->listeners[$eventListener])) {
            unset($this->listeners[$eventListener]);
        }
    }

    private function getActualListenerType(EventListenerInterface $eventListener)
    {
        if ($eventListener instanceof EventListenerProxy) {
            $listenerType = $eventListener->getTargetType();
        } else {
            $listenerType = get_class($eventListener);
        }
        return $listenerType;
    }

}
