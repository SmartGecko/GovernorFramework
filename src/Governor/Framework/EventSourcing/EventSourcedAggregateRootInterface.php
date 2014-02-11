<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\EventSourcing;

use Governor\Framework\Domain\AggregateRootInterface;
use Governor\Framework\Domain\DomainEventStreamInterface;

/**
 *
 * @author david
 */
interface EventSourcedAggregateRootInterface extends AggregateRootInterface
{

    public function initializeState(DomainEventStreamInterface $domainEventStream);
}
