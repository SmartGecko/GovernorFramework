<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\EventStore\Orm;

use Doctrine\ORM\EntityManager;
use Governor\Framework\Domain\DomainEventStreamInterface;
use Governor\Framework\EventStore\EventStoreInterface;
use Governor\Framework\EventStore\PartialEventStreamSupportInterface;
use Governor\Framework\EventStore\SnapshotEventStoreInterface;
use Governor\Framework\Serializer\SerializerInterface;
use Governor\Framework\Serializer\MessageSerializer;

/**
 * Description of OrmEventStore
 *
 */
class OrmEventStore implements EventStoreInterface, PartialEventStreamSupportInterface, SnapshotEventStoreInterface
{

    /**
     * @var EntityManager 
     */
    private $entityManager;

    /**
     * @var MessageSerializer 
     */
    private $serializer;

    public function __construct(EntityManager $entityManager,
            SerializerInterface $serializer)
    {
        $this->entityManager = $entityManager;
        $this->serializer = new MessageSerializer($serializer);
    }

    public function appendEvents($type, DomainEventStreamInterface $events)
    {
        
    }

    public function appendSnapshotEvent($type,
            DomainEventStreamInterface $snapshotEvent)
    {
        
    }

    public function readEvents($type, $identifier, $firstSequenceNumber,
            $lastSequenceNumber = null)
    {
        
    }

}
