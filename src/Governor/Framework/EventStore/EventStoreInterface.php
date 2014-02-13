<?php

namespace Governor\Framework\EventStore;

use Governor\Framework\Domain\DomainEventStreamInterface;

/**
 * Stores events grouped together in streams identified by UUID.
 *
 * The EventStore is used to implement EventSourcing in Governor\Framework
 * and is not neeeded otherwise.
 */
interface EventStoreInterface
{

    /**
     * Append the events in the given {@link DomainEventStreamInterface stream} to the event store.
     *
     * @param type   The type descriptor of the object to store
     * @param events The event stream containing the events to store
     * @throws EventStoreException if an error occurs while storing the events in the event stream
     */
    public function appendEvents($type, DomainEventStreamInterface $events);

    /**
     * Read the events of the aggregate identified by the given type and identifier that allow the current aggregate
     * state to be rebuilt. Implementations may omit or replace events (e.g. by using snapshot events) from the stream
     * for performance purposes.
     *
     * @param type       The type descriptor of the object to retrieve
     * @param identifier The unique aggregate identifier of the events to load
     * @return an event stream containing the events of the aggregate
     *
     * @throws EventStoreException if an error occurs while reading the events in the event stream
     */
    public function readEvents($type, $identifier);
}
