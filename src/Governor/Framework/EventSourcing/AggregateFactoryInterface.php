<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\EventSourcing;

use Governor\Framework\Domain\DomainEventMessageInterface;

interface AggregateFactoryInterface
{

    /**
     * Instantiate the aggregate using the given aggregate identifier and first event. The first event of the event
     * stream is passed to allow the factory to identify the actual implementation type of the aggregate to create. The
     * first event can be either the event that created the aggregate or, when using event sourcing, a snapshot event.
     * In either case, the event should be designed, such that these events contain enough information to deduct the
     * actual aggregate type.
     *
     * @param aggregateIdentifier the aggregate identifier of the aggregate to instantiate
     * @param firstEvent          The first event in the event stream. This is either the event generated during
     *                            creation of the aggregate, or a snapshot event
     * @return an aggregate ready for initialization using a DomainEventStream.
     */
    public function createAggregate($aggregateIdentifier,
        DomainEventMessageInterface $firstEvent);

    /**
     * Returns the type identifier for this aggregate factory. The type identifier is used by the EventStore to
     * organize data related to the same type of aggregate.
     * <p/>
     * Tip: in most cases, the simple class name would be a good start.
     *
     * @return the type identifier of the aggregates this repository stores
     */
    public function getTypeIdentifier();

    /**
     * Returns the type of aggregate this factory creates. All instances created by this factory must be an
     * <code>instanceOf</code> this type.
     *
     * @return The type of aggregate created by this factory
     */
    public function getAggregateType();
}
