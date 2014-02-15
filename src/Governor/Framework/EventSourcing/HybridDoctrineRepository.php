<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\EventSourcing;

use Governor\Framework\Repository\GenericDoctrineRepository;
use Governor\Framework\EventHandling\EventBusInterface;
use Governor\Framework\Repository\LockManagerInterface;
use Governor\Framework\EventStore\EventStoreInterface;
use Governor\Framework\Domain\AggregateRootInterface;
use Doctrine\ORM\EntityManager;

/**
 * Description of HybridDoctrineRepository
 *
 * @author david
 */
class HybridDoctrineRepository extends GenericDoctrineRepository
{

    private $eventStore;
    private $aggregateTypeIdentifier;

    public function __construct($className, EventBusInterface $eventBus,
        LockManagerInterface $lockManager, EntityManager $entityManager,
        EventStoreInterface $eventStore)
    {
        parent::__construct($className, $eventBus, $lockManager, $entityManager);
        $this->eventStore = $eventStore;
        
        $reflClass = new \ReflectionClass($className);
        $this->aggregateTypeIdentifier = $reflClass->getShortName();
    }

    protected function doDeleteWithLock(AggregateRootInterface $aggregate)
    {
        if (null !== $this->eventStore) {
            $this->eventStore->appendEvents($this->getTypeIdentifier(),
                $aggregate->getUncommittedEvents());
        }

        parent::doDeleteWithLock($aggregate);
    }

    protected function doSaveWithLock(AggregateRootInterface $aggregate)
    {
        if (null !== $this->eventStore) {
            $this->eventStore->appendEvents($this->getTypeIdentifier(),
                $aggregate->getUncommittedEvents());
        }

        parent::doSaveWithLock($aggregate);
    }

    /**
     * Returns the type identifier to use when appending events in the event store. Default to the simple class name of
     * the aggregateType provided in the constructor.
     *
     * @return the type identifier to use when appending events in the event store.
     */
    protected function getTypeIdentifier()
    {
        return $this->aggregateTypeIdentifier;
    }

}
