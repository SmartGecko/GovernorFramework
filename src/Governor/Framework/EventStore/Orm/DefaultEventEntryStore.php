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
                        "SELECT new Governor\Framework\Serializer\SimpleSerializedDomainEventData(" .
                        "e.eventIdentifier, e.aggregateIdentifier, e.scn, " .
                        "e.timestamp, e.payloadType, e.payloadRevision, e.payload, e.metaData) " .
                        "FROM Governor\Framework\EventStore\Orm\DomainEventEntry e " .
                        "WHERE e.aggregateIdentifier = :id AND e.type = :type " .
                        "AND e.scn >= :seq " .
                        "ORDER BY e.scn ASC")
                ->setParameters(array(':id' => $identifier, ':type' => $aggregateType,
            ':seq' => $firstscn));

        return $query->iterate();
    }

    public function fetchFiltered($whereClause, array $parameters, $batchSize,
            EntityManager $entityManager)
    {

    }

    public function loadLastSnapshotEvent($aggregateType, $identifier,
            EntityManager $entityManager)
    {
        $query = $entityManager->
                createQuery("SELECT new Governor\Framework\Serializer\SimpleSerializedDomainEventData(" .
                        "e.eventIdentifier, e.aggregateIdentifier, e.scn, " .
                        "e.timestamp, e.payloadType, e.payloadRevision, e.payload, e.metaData) " .
                        "FROM Governor\Framework\EventStore\Orm\SnapshotEventEntry e " .
                        "WHERE e.aggregateIdentifier = :id AND e.type = :type " .
                        "ORDER BY e.scn DESC")
                ->setFirstResult(0)
                ->setMaxResults(1)
                ->setParameters(array(':id' => $identifier, ':type' => $aggregateType));
        
        $entries = $query->getResult();
        
        if (count($entries) < 1) {
            return null;
        }

        return $entries[0];
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
        $redundantSnapshots = $this->findRedundantSnapshots($type,
                $mostRecentSnapshotEvent, $maxSnapshotsArchived, $entityManager);
        if (count($redundantSnapshots)) {
            $scnOfFirstToPrune = current($redundantSnapshots);

            $entityManager->createQuery("DELETE FROM Governor\Framework\EventStore\Orm\SnapshotEventEntry e " .
                            "WHERE e.type = :type " .
                            "AND e.aggregateIdentifier = :aggregateIdentifier " .
                            "AND e.scn <= :scnOfFirstToPrune")
                    ->setParameters(array(':type' => $type, ':aggregateIdentifier' => $mostRecentSnapshotEvent->getAggregateIdentifier(),
                        ':scnOfFirstToPrune' => $scnOfFirstToPrune))->execute();
        }
    }

    private function findRedundantSnapshots($type,
            DomainEventMessageInterface $snapshotEvent, $maxSnapshotsArchived,
            EntityManager $entityManager)
    {
        $query = $entityManager->createQuery(
                        "SELECT e.scn FROM Governor\Framework\EventStore\Orm\SnapshotEventEntry e " .
                        "WHERE e.type = :type AND e.aggregateIdentifier = :aggregateIdentifier " .
                        "ORDER BY e.scn DESC")
                ->setFirstResult($maxSnapshotsArchived)
                ->setMaxResults(1)
                ->setParameters(array(':type' => $type, ':aggregateIdentifier' => $snapshotEvent->getAggregateIdentifier()));

        return $query->getResult();
    }

}
