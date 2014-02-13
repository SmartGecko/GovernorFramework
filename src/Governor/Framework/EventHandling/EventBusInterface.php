<?php

namespace Governor\Framework\EventHandling;

use Governor\Framework\Domain\EventMessageInterface;

/**
 * Event Message bus handles all events that were emitted by domain objects.
 *
 * The Event Message Bus finds all event handles that listen to a certain
 * event, and then triggers these handlers one after another. Exceptions in
 * event handlers should be swallowed. Intelligent Event Systems should know
 * how to retry failing events until they are successful or failed too often.
 */
interface EventBusInterface
{

    /**
     * Publish an event to the bus.
     *
     * @param array<EventMessageInterface> $event
     * @return void
     */
    public function publish(array $events);

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
    public function unsubscribe($eventName,
        EventListenerInterface $eventListener);
}
