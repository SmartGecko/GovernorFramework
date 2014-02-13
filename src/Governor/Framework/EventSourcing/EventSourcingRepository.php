<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\EventSourcing;

use Governor\Framework\Repository\LockingRepository;
use Governor\Framework\Domain\AggregateRootInterface;
use Governor\Framework\EventHandling\EventBusInterface;
use Governor\Framework\Repository\LockManagerInterface;
use Governor\Framework\EventStore\EventStoreInterface;

/**
 * Description of EventSourcingRepository
 *
 * @author 255196
 */
class EventSourcingRepository extends LockingRepository
{

    private $eventStore;

    public function __construct($className, EventBusInterface $eventBus,
            LockManagerInterface $lockManager, EventStoreInterface $eventStore)
    {
        parent::__construct($className, $eventBus, $lockManager);
        $this->eventStore = $eventStore;
    }

    protected function doDeleteWithLock(AggregateRootInterface $aggregate)
    {
        
    }

    protected function doLoad($id, $exceptedVersion)
    {
        
    }

    protected function doSaveWithLock(AggregateRootInterface $aggregate)
    {
        
    }

}
