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
use Governor\Framework\Serializer\SerializedDomainEventDataInterface;

/**
 * Interface describing the mechanism that stores Events into the backing data store.
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a> 
 */
interface EventEntryStoreInterface
{

    /**
     * Persists the given <code>event</code> which has been serialized into <code>serializedEvent</code> in the
     * backing data store using given <code>entityManager</code>.
     * <p/>
     * These events should be returned by the <code>fetchAggregateStream(...)</code> methods.
     *
     * @param string $aggregateType      The type identifier of the aggregate that generated the event
     * @param DomainEventMessageInterface $event   The actual event instance. May be used to extract relevant meta data
     * @param SerializedObjectInterface $serializedPayload  The serialized payload of the event
     * @param SerializedObjectInterface $serializedMetaData The serialized MetaData of the event
     * @param EntityManager $entityManager      The entity manager providing access to the data store
     */
    public function persistEvent($aggregateType,
        DomainEventMessageInterface $event,
        SerializedObjectInterface $serializedPayload,
        SerializedObjectInterface $serializedMetaData,
        EntityManager $entityManager);

    /**
     * Load the last known snapshot event for aggregate of given <code>type</code> with given <code>identifier</code>
     * using given <code>entityManager</code>.
     *
     * @param string $aggregateType The type identifier of the aggregate that generated the event
     * @param string $identifier    The identifier of the aggregate to load the snapshot for
     * @param EntityManager $entityManager The entity manager providing access to the data store
     * @return SerializedDomainEventDataInterface the serialized representation of the last known snapshot event
     */
    public function loadLastSnapshotEvent($aggregateType, $identifier,
        EntityManager $entityManager);

    /**
     * Creates an iterator that iterates through the events for an aggregate of given <code>type</code> and given
     * <code>identifier</code> starting at given <code>firstSequenceNumber</code>. When using batched fetching, the
     * given <code>batchSize</code> should be used. The given <code>entityManager</code> provides access to the backing
     * data store.
     * <p/>
     * Note that the result is expected to be ordered by sequence number, with the lowest number first.
     *
     * @param string $aggregateType       The type identifier of the aggregate that generated the event
     * @param string $identifier          The identifier of the aggregate to load the snapshot for
     * @param integer $firstScn The sequence number of the first event to include in the batch
     * @param integer $batchSize           The number of entries to include in the batch (if available)
     * @param EntityManager $entityManager       The entity manager providing access to the data store
     * @return \Iterator a List of serialized representations of Events included in this batch
     */
    public function fetchAggregateStream($aggregateType, $identifier, $firstScn,
        $batchSize, EntityManager $entityManager);

    /**
     * Creates an iterator that iterates through the Events that conform to the given JPA <code>whereClause</code>.
     * When the implementation uses batched fetching, it should use given <code>batchSize</code>. The given
     * <code>parameters</code> provide the values for the placeholders used in the where clause.
     * <p/>
     * The "WHERE" keyword must not be included in the <code>whereClause</code>. If the clause is null or an empty
     * String, no filters are applied, and an iterator is returned that scans <em>all</em> events in the event store.
     * <p/>
     * The iterator should return events in the order they were added to the event store. In either case, it must
     * ensure that events originating from the same aggregate are always returned with the lowest sequence number
     * first.
     *
     * @param string $whereClause   The JPA clause to be included after the WHERE keyword
     * @param array $parameters    A map containing all the parameter values for parameter keys included in the where clause
     * @param integer $batchSize     The total number of events to return in this batch
     * @param EntityManager $entityManager The entity manager providing access to the data store
     * @return \Iterator a List of serialized representations of Events included in this batch
     */
    public function fetchFiltered($whereClause, array $parameters, $batchSize,
        EntityManager $entityManager);

    /**
     * Removes old snapshots from the storage for an aggregate of given <code>type</code> that generated the given
     * <code>mostRecentSnapshotEvent</code>. A number of <code>maxSnapshotsArchived</code> is expected to remain in the
     * archive after pruning, unless that number of snapshots has not been created yet. The given
     * <code>entityManager</code> provides access to the data store.
     *
     * @param string $type                    the type of the aggregate for which to prune snapshots
     * @param DomainEventMessageInterface $mostRecentSnapshotEvent the last appended snapshot event
     * @param integer $maxSnapshotsArchived    the number of snapshots that may remain archived
     * @param EntityManager $entityManager           the entityManager providing access to the data store
     */
    public function pruneSnapshots($type,
        DomainEventMessageInterface $mostRecentSnapshotEvent,
        $maxSnapshotsArchived, EntityManager $entityManager);

    /**
     * Persists the given <code>event</code> which has been serialized into <code>serializedEvent</code> in the
     * backing data store using given <code>entityManager</code>.
     * <p/>
     * These snapshot events should be returned by the <code>loadLastSnapshotEvent(...)</code> methods.
     *
     * @param string $aggregateType      The type identifier of the aggregate that generated the event
     * @param DomainEventMessageInterface $snapshotEvent      The actual snapshot event instance. May be used to extract relevant meta data
     * @param SerializedObjectInterface $serializedPayload  The serialized payload of the event
     * @param SerializedObjectInterface $serializedMetaData The serialized MetaData of the event
     * @param EntityManager $entityManager      The entity manager providing access to the data store
     */
    public function persistSnapshot($aggregateType,
        DomainEventMessageInterface $snapshotEvent,
        SerializedObjectInterface $serializedPayload,
        SerializedObjectInterface $serializedMetaData,
        EntityManager $entityManager);
}
