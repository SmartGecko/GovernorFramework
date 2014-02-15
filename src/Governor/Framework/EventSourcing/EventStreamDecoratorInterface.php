<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\EventSourcing;

use Governor\Framework\Domain\DomainEventStreamInterface;

/**
 *
 * @author david
 */
interface EventStreamDecorator
{

    /**
     * Called when an event stream is read from the event store.
     * <p/>
     * Note that a stream is read-once, similar to InputStream. If you read from the stream, make sure to store the read
     * events and pass them to the chain. Usually, it is best to decorate the given <code>eventStream</code> and pass
     * that to the chain.
     *
     * @param aggregateType       The type of aggregate events are being read for
     * @param aggregateIdentifier The identifier of the aggregate events are loaded for
     * @param eventStream         The eventStream containing the events to append to the event store  @return The
     *                            decorated event stream
     * @return the decorated event stream
     */
    public function decorateForRead($aggregateType, $aggregateIdentifier,
        DomainEventStreamInterface $eventStream);

    /**
     * Called when an event stream is appended to the event store.
     * <p/>
     * Note that a stream is read-once, similar to InputStream. If you read from the stream, make sure to store the read
     * events and pass them to the chain. Usually, it is best to decorate the given <code>eventStream</code> and pass
     * that to the chain.
     *
     * @param aggregateType The type of aggregate events are being appended for
     * @param aggregate     The aggregate for which the events are being stored
     * @param eventStream   The eventStream containing the events to append to the event store  @return The decorated
     *                      event stream
     * @return the decorated event stream
     */
    public function decorateForAppend($aggregateType,
        EventSourcedAggregateRootInterface $aggregate,
        DomainEventStreamInterface $eventStream);
}
