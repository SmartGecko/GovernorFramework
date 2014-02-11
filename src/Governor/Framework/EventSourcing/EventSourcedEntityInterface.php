<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\EventSourcing;

use Governor\Framework\Domain\DomainEventMessageInterface;

/**
 *
 * @author david
 */
interface EventSourcedEntityInterface
{
     /**
     * Register the aggregate root with this entity. The entity must use this aggregate root to apply Domain Events.
     * The aggregate root is responsible for tracking all applied events.
     * <p/>
     * A parent entity is responsible for invoking this method on its child entities prior to propagating events to it.
     * Typically, this means all entities have their aggregate root set before any actions are taken on it.
     *
     * @param aggregateRootToRegister the root of the aggregate this entity is part of.
     */
    public function registerAggregateRoot(AbstractEventSourcedAggregateRoot $aggregateRootToRegister);

    /**
     * Report the given <code>event</code> for handling in the current instance (<code>this</code>), as well as all the
     * entities referenced by this instance.
     *
     * @param \Governor\Framework\Domain\DomainEventMessageInterface $event The event to handle
     */
    public function handleRecursively(DomainEventMessageInterface $event);
}
