<?php

/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * The software is based on the Axon Framework project which is
 * licensed under the Apache 2.0 license. For more information on the Axon Framework
 * see <http://www.axonframework.org/>.
 * 
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.governor-framework.org/>.
 */

namespace Governor\Framework\EventStore\Orm;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Governor\Framework\Common\Logging\NullLogger;
use Governor\Framework\Common\IdentifierValidator;
use Governor\Framework\EventStore\EventVisitorInterface;
use Governor\Framework\EventStore\EventStreamNotFoundException;
use Governor\Framework\EventStore\Management\CriteriaInterface;
use Governor\Framework\EventStore\Management\EventStoreManagementInterface;
use Governor\Framework\EventStore\Orm\Criteria\OrmCriteriaBuilder;
use Governor\Framework\EventStore\Orm\Criteria\ParameterRegistry;
use Governor\Framework\Domain\GenericDomainEventMessage;
use Governor\Framework\Domain\DomainEventStreamInterface;
use Governor\Framework\Domain\DomainEventMessageInterface;
use Governor\Framework\EventStore\EventStoreInterface;
use Governor\Framework\EventStore\SnapshotEventStoreInterface;
use Governor\Framework\Serializer\SerializerInterface;
use Governor\Framework\Serializer\MessageSerializer;

/**
 * Implementation of the {@see EventStoreInterface} backed by Doctrine ORM.
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
class OrmEventStore implements EventStoreInterface, EventStoreManagementInterface, SnapshotEventStoreInterface
{

    const DEFAULT_BATCH_SIZE = 100;
    const DEFAULT_MAX_SNAPSHOTS_ARCHIVED = 1;

    /**
     * @var integer
     */
    private $batchSize = self::DEFAULT_BATCH_SIZE;

    /**
     * @var integer
     */
    private $maxSnapshotsArchived = self::DEFAULT_MAX_SNAPSHOTS_ARCHIVED;

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

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Creates a new instance of the OrmEventStore.
     *
     * @param EntityManager $entityManager ORM entity manager.
     * @param SerializerInterface $serializer Serializer implementation.
     * @param EventEntryStoreInterface|null $entryStore Entry store implementation
     */
    public function __construct(
        EntityManager $entityManager,
        SerializerInterface $serializer,
        EventEntryStoreInterface $entryStore = null
    ) {
        $this->entityManager = $entityManager;
        $this->serializer = new MessageSerializer($serializer);
        $this->entryStore = null === $entryStore ? new DefaultEventEntryStore() : $entryStore;
        $this->logger = new NullLogger();
    }

    /**
     * Sets the number of events that should be read at each database access. When more than this number of events must
     * be read to rebuild an aggregate's state, the events are read in batches of this size. Defaults to 100.
     * <p/>
     * Tip: if you use a snapshotter, make sure to choose snapshot trigger and batch size such that a single batch will
     * generally retrieve all events required to rebuild an aggregate's state.
     *
     * @param integer $batchSize the number of events to read on each database access. Default to 100.
     */
    public function setBatchSize($batchSize)
    {
        $this->batchSize = $batchSize;
    }

    /**
     * Sets the maximum number of snapshots to archive for an aggregate. The EventStore will keep at most this number
     * of
     * snapshots per aggregate.
     * <p/>
     * Defaults to {@value #DEFAULT_MAX_SNAPSHOTS_ARCHIVED}.
     *
     * @param integer $maxSnapshotsArchived The maximum number of snapshots to archive for an aggregate. A value less than 1
     *                             disables pruning of snapshots.
     */
    public function setMaxSnapshotsArchived($maxSnapshotsArchived)
    {
        $this->maxSnapshotsArchived = $maxSnapshotsArchived;
    }

    public function appendEvents($type, DomainEventStreamInterface $events)
    {
        while ($events->hasNext()) {
            $event = $events->next();
            IdentifierValidator::validateIdentifier($event->getAggregateIdentifier());
            $serializedPayload = $this->serializer->serializePayload($event);
            $serializedMetaData = $this->serializer->serializeMetaData($event);

            $this->entryStore->persistEvent(
                $type,
                $event,
                $serializedPayload,
                $serializedMetaData,
                $this->entityManager
            );
        }

        $this->entityManager->flush();
    }

    public function appendSnapshotEvent(
        $type,
        DomainEventMessageInterface $snapshotEvent
    ) {
        // Persist snapshot before pruning redundant archived ones, in order to prevent snapshot misses when reloading
        // an aggregate, which may occur when a READ_UNCOMMITTED transaction isolation level is used.
        $serializedPayload = $this->serializer->serializePayload($snapshotEvent);
        $serializedMetaData = $this->serializer->serializeMetaData($snapshotEvent);
        $this->entryStore->persistSnapshot(
            $type,
            $snapshotEvent,
            $serializedPayload,
            $serializedMetaData,
            $this->entityManager
        );

        if ($this->maxSnapshotsArchived > 0) {
            $this->entryStore->pruneSnapshots(
                $type,
                $snapshotEvent,
                $this->maxSnapshotsArchived,
                $this->entityManager
            );
        }
    }

    public function readEvents($type, $identifier)
    {
        $snapshotScn = -1;
        $snapshotEvent = null;
        $lastSnapshotEvent = $this->entryStore->loadLastSnapshotEvent(
            $type,
            $identifier,
            $this->entityManager
        );

        if (null !== $lastSnapshotEvent) {
            try {
                $snapshotEvent = new GenericDomainEventMessage(
                    $identifier, $lastSnapshotEvent->getScn(),
                    $this->serializer->deserialize($lastSnapshotEvent->getPayload()),
                    $this->serializer->deserialize($lastSnapshotEvent->getMetaData())
                );


                $snapshotScn = $snapshotEvent->getScn();
            } catch (\RuntimeException $ex) {
                $this->logger->warn(
                    "Error while reading snapshot event entry. ".
                    "Reconstructing aggregate on entire event stream. Caused by: {class} {message}",
                    array(
                        'class' => get_class($ex),
                        'message' => $ex->getMessage()
                    )
                );
            }
        }

        $entries = $this->entryStore->fetchAggregateStream(
            $type,
            $identifier,
            $snapshotScn,
            $this->batchSize,
            $this->entityManager
        );

        if ($snapshotEvent === null && !$entries->valid()) {
            throw new EventStreamNotFoundException($type, $identifier);
        }

        return new OrmDomainEventStream(
            $this->serializer, $entries,
            $identifier, $snapshotEvent
        );
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function visitEvents(
        EventVisitorInterface $visitor,
        CriteriaInterface $criteria = null
    ) {
        $whereClause = '';
        $parameters = [];

        if (null !== $criteria) {
            $paramRegistry = new ParameterRegistry();
            $criteria->parse('e', $whereClause, $paramRegistry);
            $parameters = $paramRegistry->getParameters();
        }

        $batch = $this->entryStore->fetchFiltered(
            $whereClause,
            $parameters,
            $this->batchSize,
            $this->entityManager
        );
        $eventStream = new OrmDomainEventStream(
            $this->serializer, $batch, null,
            null
        );

        while ($eventStream->hasNext()) {
            $visitor->doWithEvent($eventStream->next());
        }
    }

    public function newCriteriaBuilder()
    {
        return new OrmCriteriaBuilder();
    }

}
