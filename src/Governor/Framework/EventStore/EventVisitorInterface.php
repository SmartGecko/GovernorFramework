<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\EventStore;

use Governor\Framework\Domain\DomainEventMessageInterface;

/**
 * Interface describing an instance of a visitor that receives events for processing.
 */
interface EventVisitor
{

    /**
     * Called for each event loaded by the event store.
     *
     * @param domainEvent The loaded event
     */
    public function doWithEvent(DomainEventMessageInterface $domainEvent);
}
