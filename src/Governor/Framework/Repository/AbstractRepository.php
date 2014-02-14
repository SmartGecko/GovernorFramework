<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Repository;

use Governor\Framework\Domain\AggregateRootInterface;
use Governor\Framework\UnitOfWork\CurrentUnitOfWork;
use Governor\Framework\UnitOfWork\SaveAggregateCallbackInterface;
use Governor\Framework\EventHandling\EventBusInterface;

/**
 * Description of AbstractRepository
 *
 * @author david
 */
abstract class AbstractRepository implements RepositoryInterface
{
    
    private $eventBus;
    private $className;
    private $saveAggregateCallback;

    public function __construct($className, EventBusInterface $eventBus)
    {
        $this->className = $className;        
        $this->eventBus = $eventBus;

        $repos = $this;
        $this->saveAggregateCallback = new SimpleSaveAggregateCallback(function (AggregateRootInterface $aggregateRoot) use ($repos) {
            if ($aggregateRoot->isDeleted()) {
                $repos->doDelete($aggregateRoot);
            } else {
                $repos->doSave($aggregateRoot);
            }

            $aggregateRoot->commitEvents();
        });
    }

    public function load($id, $expectedVersion = null)
    {
        $object = $this->doLoad($id, $expectedVersion);
        $this->validateOnLoad($object, $expectedVersion);

        return CurrentUnitOfWork::get()->registerAggregate($object,
                $this->eventBus, $this->saveAggregateCallback);
    }

    public function add(AggregateRootInterface $aggregateRoot)
    {
        if (null !== $aggregateRoot->getVersion()) {
            throw new \InvalidArgumentException("Only newly created (unpersisted) aggregates may be added.");
        }

        if (!$this->supportsClass(get_class($aggregateRoot))) {
            throw new \InvalidArgumentException(sprintf("This repository supports %s, but got %s",
                $this->className, get_class($aggregateRoot)));
        }

        CurrentUnitOfWork::get()->registerAggregate($aggregateRoot,
            $this->eventBus, $this->saveAggregateCallback);
    }

    public function supportsClass($class)
    {
        return $this->className === $class;
    }

    public function getClass()
    {
        return $this->className;
    }

    protected function validateOnLoad(AggregateRootInterface $object,
        $expectedVersion)
    {
        if (null !== $expectedVersion && null !== $object->getVersion() &&
            $expectedVersion !== $object->getVersion()) {
            throw new ConflictingAggregateVersionException($object->getIdentifier(),
            $expectedVersion, $object->getVersion());
        }
    }

    protected abstract function doSave(AggregateRootInterface $object);

    protected abstract function doLoad($id, $exceptedVersion);

    protected abstract function doDelete(AggregateRootInterface $object);
}
