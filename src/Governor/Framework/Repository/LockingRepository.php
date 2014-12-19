<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Repository;

use Governor\Framework\Domain\AggregateRootInterface;
use Governor\Framework\EventHandling\EventBusInterface;

/**
 * Description of LockingRepository
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a> 
 */
abstract class LockingRepository extends AbstractRepository
{

    private $lockManager;

    public function __construct($className, EventBusInterface $eventBus, LockManagerInterface $lockManager)
    {
        parent::__construct($className, $eventBus);
        $this->lockManager = $lockManager;
    }

    public function add(AggregateRootInterface $object)
    {
        $aggregateId = $object->getIdentifier();
        $this->lockManager->obtainLock($aggregateId);

        try {
            parent::add($object);
        } catch (\Exception $ex) {
            $this->lockManager->releaseLock($aggregateId);
            throw $ex;
        }
    }

    public function load($id, $expectedVersion = null)
    {
        $this->lockManager->obtainLock($id);
        try {
            $object = parent::load($id, $expectedVersion);

            return $object;
        } catch (\Exception $ex) {
            $this->lockManager->releaseLock($id);
            throw $ex;
        }
    }

    protected function doDelete(AggregateRootInterface $object)
    {
        if (null !== $object->getVersion() && !$this->lockManager->validateLock($object)) {
            throw new ConcurrencyException(sprintf(
                "The aggregate of type [%s] with identifier [%s] could not be " .
                "saved, as a valid lock is not held. Either another thread has saved an aggregate, or " .
                "the current thread had released its lock earlier on.",
                get_class($object), $object->getIdentifier()
            ));
        }

        $this->doDeleteWithLock($object);
    }

    protected function doSave(AggregateRootInterface $object)
    {
        if (null !== $object->getVersion() && !$this->lockManager->validateLock($object)) {
            throw new ConcurrencyException(sprintf(
                "The aggregate of type [%s] with identifier [%s] could not be "
                . "saved, as a valid lock is not held. Either another thread has saved an aggregate, or "
                . "the current thread had released its lock earlier on.",
                get_class($object), $object->getIdentifier()
            ));
        }

        $this->doSaveWithLock($object);
    }

    /**
     * Perform the actual saving of the aggregate. All necessary locks have been verified.
     *
     * @param AggregateRootInterface $aggregate the aggregate to store
     */
    protected abstract function doSaveWithLock(AggregateRootInterface $aggregate);

    /**
     * Perform the actual deleting of the aggregate. All necessary locks have been verifierd.
     *
     * @param AggregateRootInterface $aggregate the aggregate to delete
     */
    protected abstract function doDeleteWithLock(AggregateRootInterface $aggregate);
}
