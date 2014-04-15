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
use Governor\Framework\Repository\AggregateNotFoundException;
use Governor\Framework\EventStore\EventStreamNotFoundException;
use Governor\Framework\UnitOfWork\CurrentUnitOfWork;

/**
 * Description of EventSourcingRepository
 *
 * @author 255196
 */
class EventSourcingRepository extends LockingRepository
{

    private $eventStore;
    private $factory;
    private $conflictResolver;
    private $eventStreamDecorators = array();

    /**
     * Creates a new EventSourcingRepository with the given parameters.
     * 
     * @param string $className
     * @param \Governor\Framework\EventHandling\EventBusInterface $eventBus
     * @param \Governor\Framework\Repository\LockManagerInterface $lockManager
     * @param \Governor\Framework\EventStore\EventStoreInterface $eventStore
     * @param \Governor\Framework\EventSourcing\AggregateFactoryInterface $factory
     */
    public function __construct($className, EventBusInterface $eventBus,
        LockManagerInterface $lockManager, EventStoreInterface $eventStore,
        AggregateFactoryInterface $factory)
    {
        $this->validateEventSourcedAggregate($className);

        parent::__construct($className, $eventBus, $lockManager);
        $this->eventStore = $eventStore;
        $this->factory = $factory;
        $this->conflictResolver = null;
    }

    protected function doDeleteWithLock(AggregateRootInterface $aggregate)
    {
        $this->doSaveWithLock($aggregate);
    }

    protected function doLoad($id, $exceptedVersion)
    {
        try {
            $events = $this->eventStore->readEvents($this->getTypeIdentifier(),
                $id);
        } catch (EventStreamNotFoundException $ex) {
            throw new AggregateNotFoundException($id, "The aggregate was not found", $ex);
        }

        foreach ($this->eventStreamDecorators as $decorator) {
            $events = $decorator->decorateForRead($this->getTypeIdentifier(),
                $id, $events);
        }
        
        $aggregate = $this->factory->createAggregate($id, $events->peek());
        $unseenEvents = array();
                
        $aggregate->initializeState(new CapturingEventStream($events,
            $unseenEvents, $exceptedVersion));
        if ($aggregate->isDeleted()) {
            throw new AggregateDeletedException($id);
        }

        CurrentUnitOfWork::get()->registerListener(new ConflictResolvingListener($aggregate, $unseenEvents, $this->conflictResolver));

        return $aggregate;
    }

    protected function doSaveWithLock(AggregateRootInterface $aggregate)
    {
        $eventStream = $aggregate->getUncommittedEvents();
        $iterator = new \ArrayIterator(array_reverse($this->eventStreamDecorators));

        while ($iterator->valid()) {
            $eventStream = $iterator->current()->decorateForAppend($this->getTypeIdentifier(),
                $aggregate, $eventStream);
            $iterator->next();
        }
                
        $this->eventStore->appendEvents($this->getTypeIdentifier(), $eventStream);
    }

    private function validateEventSourcedAggregate($className)
    {
        $reflClass = new \ReflectionClass($className);

        if (!$reflClass->implementsInterface('Governor\Framework\EventSourcing\EventSourcedAggregateRootInterface')) {
            throw new \InvalidArgumentException(sprintf("EventSourcingRepository aggregates need to implements EventSourcedAggregateRootInterface, %s does not.",
                $className));
        }
    }

    /**
     * Returns the type identifier of the aggregates in this repository.
     * 
     * @return string
     * @throws \RuntimeException
     */
    public function getTypeIdentifier()
    {
        if (null === $this->factory) {
            throw new \RuntimeException("Either an aggregate factory must be configured (recommended), " .
                "or the getTypeIdentifier() method must be overridden.");
        }

        return $this->factory->getTypeIdentifier();
    }

    /**
     * Sets the conflict resolver to use for this repository. If not set (or <code>null</code>), the repository will
     * throw an exception if any unexpected changes appear in loaded aggregates.
     *
     * @param ConflictResolverInterface $conflictResolver The conflict resolver to use for this repository
     */
    public function setConflictResolver(ConflictResolverInterface $conflictResolver)
    {
        $this->conflictResolver = $conflictResolver;
    }

    protected function validateOnLoad(AggregateRootInterface $object,
        $expectedVersion)
    {
        if (null === $this->conflictResolver) {
            parent::validateOnLoad($object, $expectedVersion);
        }
    }

}
