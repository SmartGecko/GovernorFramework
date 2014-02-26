<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\EventStore\Orm;

use Doctrine\ORM\EntityManager;
use Governor\Framework\Domain\DomainEventMessageInterface;
use Governor\Framework\Serializer\SerializedObjectInterface;

/**
 * Description of DefaultEventEntryStore
 *
 * @author david
 */
class DefaultEventEntryStore implements EventEntryStoreInterface
{

    public function fetchAggregateStream($aggregateType, $identifier, $firstscn,
        $batchSize, EntityManager $entityManager)
    {
        $query = $entityManager->createQuery(
                "SELECT new SimpleSerializedDomainEventData(" .
                "e.eventIdentifier, e.aggregateIdentifier, e.sequenceNumber, " .
                "e.timeStamp, e.payloadType, e.payloadRevision, e.payload, e.metaData) " .
                "FROM DomainEventEntry e " .
                "WHERE e.aggregateIdentifier = :id AND e.type = :type " .
                "AND e.sequenceNumber >= :seq " .
                "ORDER BY e.sequenceNumber ASC")
            ->setParameters(array(':id' => $identifier, ':type' => $aggregateType,
            ':seq' => $firstscn));

        return $query->getResult();
    }

    public function fetchFiltered($whereClause, array $parameters, $batchSize,
        EntityManager $entityManager)
    {
        
    }

    public function loadLastSnapshotEvent($aggregateType, $identifier,
        EntityManager $entityManager)
    {
        $query = $entityManager->
            createQuery("SELECT new SimpleSerializedDomainEventData(" .
                "e.eventIdentifier, e.aggregateIdentifier, e.sequenceNumber, " .
                "e.timeStamp, e.payloadType, e.payloadRevision, e.payload, e.metaData) " .
                "FROM SnapshotEventEntry e " .
                "WHERE e.aggregateIdentifier = :id AND e.type = :type " .
                "ORDER BY e.sequenceNumber DESC")
            ->setParameters(array(':id' => $identifier, ':type' => $aggregateType));

        return $query->getSingleResult();
    }

    public function persistEvent($aggregateType,
        DomainEventMessageInterface $event,
        SerializedObjectInterface $serializedPayload,
        SerializedObjectInterface $serializedMetaData,
        EntityManager $entityManager)
    {
        $entityManager->persist(new DomainEventEntry($aggregateType, $event,
            $serializedPayload, $serializedMetaData));
    }

    public function persistSnapshot($aggregateType,
        DomainEventMessageInterface $snapshotEvent,
        SerializedObjectInterface $serializedPayload,
        SerializedObjectInterface $serializedMetaData,
        EntityManager $entityManager)
    {
        $entityManager->persist(new SnapshotEventEntry($aggregateType,
            $snapshotEvent, $serializedPayload, $serializedMetaData));
    }

    public function pruneSnapshots($type,
        DomainEventMessageInterface $mostRecentSnapshotEvent,
        $maxSnapshotsArchived, EntityManager $entityManager)
    {
        
    }

}
