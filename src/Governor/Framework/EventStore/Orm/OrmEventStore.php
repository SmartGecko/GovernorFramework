<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\EventStore\Orm;

use Doctrine\ORM\EntityManager;
use Governor\Framework\Common\IdentifierValidator;
use Governor\Framework\EventStore\EventStreamNotFoundException;
use Governor\Framework\Domain\GenericDomainEventMessage;
use Governor\Framework\Domain\DomainEventStreamInterface;
use Governor\Framework\Domain\DomainEventMessageInterface;
use Governor\Framework\EventStore\EventStoreInterface;
use Governor\Framework\EventStore\SnapshotEventStoreInterface;
use Governor\Framework\Serializer\SerializerInterface;
use Governor\Framework\Serializer\MessageSerializer;

/**
 * Description of OrmEventStore
 *
 */
class OrmEventStore implements EventStoreInterface, SnapshotEventStoreInterface
{

    /**
     * @var EntityManager 
     */
    private $entityManager;

    /**
     * @var MessageSerializer 
     */
    private $serializer;

    /**
     * @var EventEntryStoreInterface 
     */
    private $entryStore;

    public function __construct(EntityManager $entityManager,
            SerializerInterface $serializer) //, EventEntryStoreInterface $entryStore)
    {
        $this->entityManager = $entityManager;
        $this->serializer = new MessageSerializer($serializer);
        $this->entryStore = new DefaultEventEntryStore();
    }

    public function appendEvents($type, DomainEventStreamInterface $events)
    {
        while ($events->hasNext()) {
            $event = $events->next();            
            IdentifierValidator::validateIdentifier($event->getAggregateIdentifier());
            $serializedPayload = $this->serializer->serializePayload($event);
            $serializedMetaData = $this->serializer->serializeMetaData($event);

            $this->entryStore->persistEvent($type, $event, $serializedPayload,
                    $serializedMetaData, $this->entityManager);
        }

        $this->entityManager->flush();
    }

    public function appendSnapshotEvent($type,
            DomainEventMessageInterface $snapshotEvent)
    {
        // Persist snapshot before pruning redundant archived ones, in order to prevent snapshot misses when reloading
        // an aggregate, which may occur when a READ_UNCOMMITTED transaction isolation level is used.
        $serializedPayload = $this->serializer->serializePayload($snapshotEvent);
        $serializedMetaData = $this->serializer->serializeMetaData($snapshotEvent);
        $this->entryStore->persistSnapshot($type, $snapshotEvent,
                $serializedPayload, $serializedMetaData, $this->entityManager);

        /*
          if (maxSnapshotsArchived > 0) {
          eventEntryStore.pruneSnapshots(type, snapshotEvent, maxSnapshotsArchived,
          entityManagerProvider.getEntityManager());
          } */
    }

    public function readEvents($type, $identifier)
    {
        $snapshotScn = -1;
        $snapshotEvent = null;
        $lastSnapshotEvent = $this->entryStore->loadLastSnapshotEvent($type,
                $identifier, $this->entityManager);
        
        if (null !== $lastSnapshotEvent) {
            try {
                $snapshotEvent = new GenericDomainEventMessage(
                        $identifier, $lastSnapshotEvent->getScn(),
                        $this->serializer->deserialize($lastSnapshotEvent->getPayload()),
                        $this->serializer->deserialize($lastSnapshotEvent->getMetaData()));


                $snapshotScn = $snapshotEvent->getScn();
            } catch (\RuntimeException $ex) {
                /*   logger.warn("Error while reading snapshot event entry. "
                  + "Reconstructing aggregate on entire event stream. Caused by: {} {}",
                  ex.getClass().getName(),
                  ex.getMessage()); */
            }
        }

        $entries = $this->entryStore->fetchAggregateStream($type, $identifier,
                $snapshotScn, 10000, $this->entityManager);

        // !!! TODO implement batch fetching now we cannot detect empty result sets :(
        if ($snapshotEvent === null && empty($entries)) {
            throw new EventStreamNotFoundException($type, $identifier);
        }

        return new OrmDomainEventStream($this->serializer, $entries, $identifier, $snapshotEvent);
    }

}
