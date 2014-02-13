<?php

namespace Governor\Framework\EventHandling;

use Governor\Framework\Domain\EventMessageInterface;

interface EventListenerLocatorInterface
{

    public function getListenersFor(EventMessageInterface $eventName);

    /**
     * Subscribe the given <code>eventListener</code> to this bus. When subscribed, it will receive all events
     * published to this bus.
     * <p/>
     * If the given <code>eventListener</code> is already subscribed, nothing happens.
     *
     * @param eventListener The event listener to subscribe
     * @throws EventListenerSubscriptionFailedException
     *          if the listener could not be subscribed
     */
    public function subscribe($eventName, EventListenerInterface $eventListener);

    /**
     * Unsubscribe the given <code>eventListener</code> to this bus. When unsubscribed, it will no longer receive
     * events
     * published to this bus.
     *
     * @param eventListener The event listener to unsubscribe
     */
    public function unsubscribe($eventName, EventListenerInterface $eventListener);
}
