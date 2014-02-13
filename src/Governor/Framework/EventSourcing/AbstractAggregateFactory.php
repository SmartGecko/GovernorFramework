<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\EventSourcing;

use Governor\Framework\Domain\DomainEventMessageInterface;

/**
 * Description of AbstractAggregateFactory
 *
 * @author david
 */
abstract class AbstractAggregateFactory implements AggregateFactoryInterface
{

    public function createAggregate($aggregateIdentifier,
        DomainEventMessageInterface $firstEvent)
    {
        if (is_subclass_of($firstEvent->getPayloadType(),
                'Governor\Framework\EventSourcing\EventSourcedAggregateRootInterface')) {
            $aggregate = $firstEvent->getPayload();
        } else {
            $aggregate = $this->doCreateAggregate($aggregateIdentifier,
                $firstEvent);
        }
        
        return $this->postProcessInstance($aggregate);
    }

    /**
     * Perform any processing that must be done on an aggregate instance that was reconstructured from a Snapshot
     * Event. Implementations may choose to modify the existing instance, or return a new instance.
     * <p/>
     * This method can be safely overridden. This implementation does nothing.
     *
     * @param aggregate The aggregate to post-process.
     * @return The aggregate to initialize with the Event Stream
     */
    protected function postProcessInstance($aggregate)
    {
        return $aggregate;
    }

    /**
     * Create an uninitialized Aggregate instance with the given <code>aggregateIdentifier</code>. The given
     * <code>firstEvent</code> can be used to define the requirements of the aggregate to create.
     * <p/>
     * The given <code>firstEvent</code> is never a snapshot event.
     *
     * @param aggregateIdentifier The identifier of the aggregate to create
     * @param firstEvent          The first event in the Event Stream of the Aggregate
     * @return The aggregate instance to initialize with the Event Stream
     */
    protected abstract function doCreateAggregate($aggregateIdentifier,
        DomainEventMessageInterface $firstEvent);
}
