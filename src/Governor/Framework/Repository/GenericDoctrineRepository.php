<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Repository;

use Governor\Framework\Domain\AggregateRootInterface;
use Governor\Framework\EventHandling\EventBusInterface;
use Doctrine\ORM\EntityManager;

/**
 * Description of GenericDoctrineRepository
 *
 * @author david
 */
class GenericDoctrineRepository extends LockingRepository
{

    private $entityManager;
    private $forceFlushOnSave = true;

    public function __construct($className, EventBusInterface $eventBus,
        LockManagerInterface $lockManager, EntityManager $entityManager)
    {        
        parent::__construct($className, $eventBus, $lockManager);
        $this->entityManager = $entityManager;
    }

    protected function doDeleteWithLock(AggregateRootInterface $aggregate)
    {
        $this->entityManager->remove($aggregate);

        if ($this->forceFlushOnSave) {
            $this->entityManager->flush();
        }
    }

    protected function doSaveWithLock(AggregateRootInterface $aggregate)
    {        
        $this->entityManager->persist($aggregate);

        if ($this->forceFlushOnSave) {
            $this->entityManager->flush();
        }
    }

    protected function doLoad($id, $exceptedVersion)
    {
        $aggregate = $this->entityManager->find($this->getClass(), $id);

        if (null === $aggregate) {
            throw new AggregateNotFoundException($id,
            sprintf(
                "Aggregate [%s] with identifier [%s] not found",
                $this->getClass(), $id));
        } else if (null !== $exceptedVersion && null !== $aggregate->getVersion() && $exceptedVersion !== $aggregate->getVersion()) {
            throw new ConflictingAggregateVersionException($id,
            $exceptedVersion, $aggregate->getVersion());
        }

        return $aggregate;
    }

    public function isForceFlushOnSave()
    {
        return $this->forceFlushOnSave;
    }

    public function setForceFlushOnSave($forceFlushOnSave)
    {
        $this->forceFlushOnSave = $forceFlushOnSave;
    }

}
