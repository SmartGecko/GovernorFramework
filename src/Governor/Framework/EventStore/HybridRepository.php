<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\EventStore;


use Governor\Framework\Repository\DoctrineRepository;
use Governor\Framework\Domain\AggregateRootInterface;
use Governor\Framework\EventHandling\EventBusInterface;
use Rhumsaa\Uuid\Uuid;
use Doctrine\ORM\EntityManager;

/**
 * Description of HybridRepository
 *
 * @author david
 */
class HybridRepository extends DoctrineRepository
{

    private $eventStore;
    private $eventBus;
    private $streams = array();

    public function __construct(EntityManager $em, EventStore $eventStore,
        EventBusInterface $eventBus)
    {
        parent::__construct($em);
        $this->eventBus = $eventBus;
        $this->eventStore = $eventStore;
    }

    public function find($className, Uuid $uuid, $expectedVersion = null)
    {
        return parent::find($className, $uuid, $expectedVersion);
    }

    public function save(AggregateRootInterface $object)
    {
        parent::save($object);

        $id = (string) $object->getId();

        if (!isset($this->streams[$id])) {
            $this->streams[$id] = new DomainEventStream(
                get_class($object), $object->getId()
            );
        }

        $eventStream = $this->streams[$id];
        $eventStream->addEvents($object->pullDomainEvents());

        $transaction = $this->eventStore->commit($eventStream);

        foreach ($transaction->getCommittedEvents() as $event) {
            $event->setAggregateId($object->getId());
            $this->eventBus->publish($event);
        }
    }

}
