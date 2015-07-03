<?php
/**
 * This file is part of the SmartGecko(c) business platform.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Governor\Framework\EventStore\Mongo;

use Governor\Framework\Domain\DomainEventMessageInterface;
use Governor\Framework\Domain\DomainEventStreamInterface;
use Governor\Framework\EventStore\EventStoreException;
use Governor\Framework\EventStore\EventStoreInterface;
use Governor\Framework\EventStore\EventVisitorInterface;
use Governor\Framework\EventStore\Management\CriteriaBuilderInterface;
use Governor\Framework\EventStore\Management\CriteriaInterface;
use Governor\Framework\EventStore\Management\EventStoreManagementInterface;
use Governor\Framework\EventStore\Mongo\Criteria\MongoCriteriaBuilder;
use Governor\Framework\EventStore\PartialEventStreamSupportInterface;
use Governor\Framework\EventStore\SnapshotEventStoreInterface;
use Governor\Framework\Serializer\SerializerInterface;
use Governor\Framework\Repository\ConcurrencyException;
use Governor\Framework\EventStore\EventStreamNotFoundException;
use Governor\Framework\Common\Logging\NullLogger;
use Psr\Log\LoggerInterface;

class MongoEventStore implements EventStoreInterface, EventStoreManagementInterface, SnapshotEventStoreInterface, PartialEventStreamSupportInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var MongoTemplateInterface
     */
    private $mongoTemplate;

    /**
     * @var SerializerInterface
     */
    private $eventSerializer;

    /**
     * @var StorageStrategyInterface
     */
    private $storageStrategy;


    private $upcasterChain;

    /**
     * @param MongoTemplateInterface $mongoTemplate
     * @param SerializerInterface $eventSerializer
     * @param StorageStrategyInterface $storageStrategy
     */
    function __construct(
        MongoTemplateInterface $mongoTemplate,
        SerializerInterface $eventSerializer,
        StorageStrategyInterface $storageStrategy
    ) {
        $this->mongoTemplate = $mongoTemplate;
        $this->eventSerializer = $eventSerializer;
        $this->storageStrategy = $storageStrategy;

        $this->logger = new NullLogger();

        $this->storageStrategy->ensureIndexes(
            $this->mongoTemplate->domainEventCollection(),
            $this->mongoTemplate->snapshotEventCollection()
        );
    }


    /**
     * Append the events in the given {@link DomainEventStreamInterface stream} to the event store.
     *
     * @param string $type The type descriptor of the object to store
     * @param DomainEventStreamInterface $events The event stream containing the events to store
     * @throws ConcurrencyException if an error occurs while storing the events in the event stream
     */
    public function appendEvents($type, DomainEventStreamInterface $events)
    {
        if (!$events->hasNext()) {
            return;
        }

        $messages = [];

        while ($events->hasNext()) {
            $messages[] = $events->next();
        }

        try {
            $this->mongoTemplate->domainEventCollection()->batchInsert(
                $this->storageStrategy->createDocuments(
                    $type,
                    $this->eventSerializer,
                    $messages
                )
            );
        } catch (\MongoDuplicateKeyException $ex) {
            throw new ConcurrencyException(
                "Trying to insert an Event for an aggregate with a sequence "
                ."number that is already present in the Event Store", null, $ex
            );
        }

        $this->logger->debug("{num} events appended", ['num' => count($messages)]);
    }

    /**
     * @param string $type
     * @param string $identifier
     * @return \Governor\Framework\Domain\DomainEventMessageInterface[]
     */
    private function loadLastSnapshotEvent($type, $identifier)
    {
        $dbCursor = $this->storageStrategy->findLastSnapshot(
            $this->mongoTemplate->snapshotEventCollection(),
            $type,
            $identifier
        );

        if (!$dbCursor->hasNext()) {
            return [];
        }
        $first = $dbCursor->next();

        return $this->storageStrategy->extractEventMessages(
            $first,
            $identifier,
            $this->eventSerializer,
            $this->upcasterChain,
            false
        );
    }

    /**
     * Read the events of the aggregate identified by the given type and identifier that allow the current aggregate
     * state to be rebuilt. Implementations may omit or replace events (e.g. by using snapshot events) from the stream
     * for performance purposes.
     *
     * @param string $type The type descriptor of the object to retrieve
     * @param mixed $identifier The unique aggregate identifier of the events to load
     * @return DomainEventStreamInterface an event stream containing the events of the aggregate
     *
     * @throws EventStoreException if an error occurs while reading the events in the event stream
     */
    public function readEvents($type, $identifier)
    {
        $snapshotSequenceNumber = -1;

        $lastSnapshotCommit = $this->loadLastSnapshotEvent($type, $identifier);

        if (null !== $lastSnapshotCommit && !empty($lastSnapshotCommit)) {
            $snapshotSequenceNumber = $lastSnapshotCommit[0]->getScn();
        }

        $dbCursor = $this->storageStrategy->findEvents(
            $this->mongoTemplate->domainEventCollection(),
            $type,
            $identifier,
            $snapshotSequenceNumber + 1
        );

        $stream = new CursorBackedDomainEventStream(
            $dbCursor,
            $lastSnapshotCommit,
            $identifier,
            null,
            false,
            $this->getCursorCallback()
        );

        if (!$stream->hasNext()) {
            throw new EventStreamNotFoundException($type, $identifier);
        }

        return $stream;
    }

    /**
     * @return callable
     */
    private function getCursorCallback()
    {
        $self = $this;

        $cb = function (array $entry, $identifier) use ($self) {
            return $self->storageStrategy->extractEventMessages(
                $entry,
                $identifier,
                $self->eventSerializer,
                $self->upcasterChain,
                false
            );
        };

        return $cb;
    }

    /**
     * Returns a Stream containing events for the aggregate identified by the given {@code type} and {@code
     * identifier}, starting at the event with the given {@code firstSequenceNumber} (included) up to and including the
     * event with given {@code lastSequenceNumber}.
     * If no event with given {@code lastSequenceNumber} exists, the returned stream will simply read until the end of
     * the aggregate's events.
     * <p/>
     * The returned stream will not contain any snapshot events.
     *
     * @param string $type The type identifier of the aggregate
     * @param string $identifier The identifier of the aggregate
     * @param int $firstSequenceNumber The sequence number of the first event to find
     * @param int|null $lastSequenceNumber The sequence number of the last event in the stream
     * @return DomainEventStreamInterface a Stream containing events for the given aggregate, starting at the given first sequence number
     * @throws EventStreamNotFoundException
     */
    public function readEventsWithinScn(
        $type,
        $identifier,
        $firstSequenceNumber,
        $lastSequenceNumber = null
    ) {
        $dbCursor = $this->storageStrategy->findEvents(
            $this->mongoTemplate->domainEventCollection(),
            $type,
            $identifier,
            $firstSequenceNumber
        );

        $stream = new CursorBackedDomainEventStream(
            $dbCursor, [], $identifier, $lastSequenceNumber,
            false, $this->getCursorCallback()
        );

        if (!$stream->hasNext()) {
            throw new EventStreamNotFoundException($type, $identifier);
        }

        return $stream;
    }


    /**
     * Loads all events available in the event store and calls
     * {@link \Governor\Framework\EventStore\EventVisitorInterface::doWithEvent}
     * for each event found. Events of a single aggregate are guaranteed to be ordered by their sequence number.
     * <p/>
     * Implementations are encouraged, though not required, to supply events in the absolute chronological order.
     * <p/>
     * Processing stops when the visitor throws an exception.
     *
     * @param EventVisitorInterface $visitor The visitor the receives each loaded event
     * @param CriteriaInterface $criteria The criteria describing the events to select.
     */
    public function visitEvents(
        EventVisitorInterface $visitor,
        CriteriaInterface $criteria = null
    ) {
        $params = isset($criteria) ? $criteria->asMongoObject() : [];

        $cursor = $this->storageStrategy->findEventsByCriteria(
            $this->mongoTemplate->domainEventCollection(),
            $params
        );

        //cursor.addOption(Bytes.QUERYOPTION_NOTIMEOUT);
        $self = $this;

        $cb = function (array $entry, $identifier) use ($self) {
            return $self->storageStrategy->extractEventMessages(
                $entry,
                $identifier,
                $self->eventSerializer,
                $self->upcasterChain,
                false
            );
        };

        $events = new CursorBackedDomainEventStream($cursor, [], null, null, true, $cb);

        try {
            while ($events->hasNext()) {
                $visitor->doWithEvent($events->next());
            }
        } finally {
            $events->close();
        }
    }

    /**
     * Returns a CriteriaBuilderInterface that allows the construction of criteria for this EventStore implementation
     *
     * @return CriteriaBuilderInterface a builder to create Criteria for this Event Store.
     */
    public function newCriteriaBuilder()
    {
        return new MongoCriteriaBuilder();
    }

    /**
     * Sets a logger instance on the object
     *
     * @param LoggerInterface $logger
     * @return null
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Append the given <code>snapshotEvent</code> to the snapshot event log for the given type <code>type</code>. The
     * sequence number of the <code>snapshotEvent</code> must be equal to the sequence number of the last regular
     * domain
     * event that is included in the snapshot.
     * <p/>
     * Implementations may choose to prune snapshots upon appending a new snapshot, in order to minimize storage space.
     *
     * @param string $type The type of aggregate the event belongs to
     * @param DomainEventMessageInterface $snapshotEvent The event summarizing one or more domain events for a specific aggregate.
     * @throws ConcurrencyException
     */
    public function appendSnapshotEvent(
        $type,
        DomainEventMessageInterface $snapshotEvent
    ) {
        $dbObject = $this->storageStrategy->createDocuments($type, $this->eventSerializer, [$snapshotEvent]);

        try {
            $this->mongoTemplate->snapshotEventCollection()->batchInsert($dbObject);
        } catch (\MongoDuplicateKeyException $ex) {
            throw new ConcurrencyException(
                "Trying to insert a SnapshotEvent with aggregate identifier and sequence "
                ."number that is already present in the Event Store", null, $ex
            );
        }

        $this->logger->debug("snapshot event of type {type} appended.", ['type' => $type]);

    }

}