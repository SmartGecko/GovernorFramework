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
use Governor\Framework\EventStore\Orm\DomainEventEntry;
use Governor\Framework\EventStore\Orm\SnapshotEventEntry;
use Governor\Framework\Domain\DomainEventMessageInterface;
use Governor\Framework\Serializer\SerializedObjectInterface;

/**
 * Description of DefaultEventEntryStore
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
class DefaultEventEntryStore implements EventEntryStoreInterface
{

    /**
     * The name of the DomainEventEntry entity to use when querying for domain events.
     *
     * @return string The entity name of the DomainEventEntry subclass to use
     */
    protected function domainEventEntryEntityName()
    {
        return DomainEventEntry::class;
    }

    /**
     * The name of the SnapshotEventEntry entity to use when querying for snapshot events.
     *
     * @return string The entity name of the SnapshotEventEntry subclass to use
     */
    protected function snapshotEventEntryEntityName()
    {
        return SnapshotEventEntry::class;
    }

    /**
     * Allows for customization of the DomainEventEntry to store. Subclasses may choose to override this method to
     * use a different entity configuration.
     * <p/>
     * When overriding this method, also make sure the {@link #domainEventEntryEntityName()} method is overridden to
     * return the correct entity name.
     *
     * @param string $aggregateType The type identifier of the aggregate
     * @param DomainEventMessageInterface $event The event to be stored
     * @param SerializedObjectInterface $serializedPayload The serialized payload of the event
     * @param SerializedObjectInterface $serializedMetaData The serialized meta data of the event
     * @return mixed an ORM entity, ready to be stored using the entity manager
     */
    protected function createDomainEventEntry(
        $aggregateType,
        DomainEventMessageInterface $event,
        SerializedObjectInterface $serializedPayload,
        SerializedObjectInterface $serializedMetaData
    ) {

        return new DomainEventEntry(
            $aggregateType, $event, $serializedPayload,
            $serializedMetaData
        );
    }

    /**
     * Allows for customization of the SnapshotEventEntry to store. Subclasses may choose to override this method to
     * use a different entity configuration.
     * <p/>
     * When overriding this method, also make sure the {@link #snapshotEventEntryEntityName()} method is overridden to
     * return the correct entity name.
     *
     * @param string $aggregateType The type identifier of the aggregate
     * @param DomainEventMessageInterface $snapshotEvent The snapshot event to be stored
     * @param SerializedObjectInterface $serializedPayload The serialized payload of the event
     * @param SerializedObjectInterface $serializedMetaData The serialized meta data of the event
     * @return mixed an ORM entity, ready to be stored using the entity manager
     */
    protected function createSnapshotEventEntry(
        $aggregateType,
        DomainEventMessageInterface $snapshotEvent,
        SerializedObjectInterface $serializedPayload,
        SerializedObjectInterface $serializedMetaData
    ) {
        return new SnapshotEventEntry(
            $aggregateType, $snapshotEvent,
            $serializedPayload, $serializedMetaData
        );
    }

    public function fetchAggregateStream(
        $aggregateType,
        $identifier,
        $firstScn,
        $batchSize,
        EntityManager $entityManager
    ) {
        return new BatchingAggregateStreamIterator(
            $firstScn, $identifier,
            $aggregateType, $batchSize, $this->domainEventEntryEntityName(),
            $entityManager
        );
    }

    public function fetchFiltered(
        $whereClause,
        array $parameters,
        $batchSize,
        EntityManager $entityManager
    ) {
        return new BatchingIterator(
            $whereClause, $parameters, $batchSize,
            $this->domainEventEntryEntityName(), $entityManager
        );
    }

    public function loadLastSnapshotEvent(
        $aggregateType,
        $identifier,
        EntityManager $entityManager
    ) {
        $query = $entityManager->
        createQuery(
            "SELECT new Governor\Framework\Serializer\SimpleSerializedDomainEventData(".
            "e.eventIdentifier, e.aggregateIdentifier, e.scn, ".
            "e.timestamp, e.payloadType, e.payloadRevision, e.payload, e.metaData) ".
            "FROM ".$this->snapshotEventEntryEntityName()." e ".
            "WHERE e.aggregateIdentifier = :id AND e.type = :type ".
            "ORDER BY e.scn DESC"
        )
            ->setFirstResult(0)
            ->setMaxResults(1)
            ->setParameters(array(':id' => $identifier, ':type' => $aggregateType));

        $entries = $query->getResult();

        if (count($entries) < 1) {
            return null;
        }

        return $entries[0];
    }

    public function persistEvent(
        $aggregateType,
        DomainEventMessageInterface $event,
        SerializedObjectInterface $serializedPayload,
        SerializedObjectInterface $serializedMetaData,
        EntityManager $entityManager
    ) {
        $entityManager->persist(
            $this->createDomainEventEntry(
                $aggregateType,
                $event,
                $serializedPayload,
                $serializedMetaData
            )
        );
    }

    public function persistSnapshot(
        $aggregateType,
        DomainEventMessageInterface $snapshotEvent,
        SerializedObjectInterface $serializedPayload,
        SerializedObjectInterface $serializedMetaData,
        EntityManager $entityManager
    ) {
        $entityManager->persist(
            $this->createSnapshotEventEntry(
                $aggregateType,
                $snapshotEvent,
                $serializedPayload,
                $serializedMetaData
            )
        );
    }

    public function pruneSnapshots(
        $type,
        DomainEventMessageInterface $mostRecentSnapshotEvent,
        $maxSnapshotsArchived,
        EntityManager $entityManager
    ) {
        $redundantSnapshots = $this->findRedundantSnapshots(
            $type,
            $mostRecentSnapshotEvent,
            $maxSnapshotsArchived,
            $entityManager
        );

        if (count($redundantSnapshots)) {
            $scnOfFirstToPrune = current($redundantSnapshots);

            $entityManager->createQuery(
                "DELETE FROM ".$this->snapshotEventEntryEntityName()." e ".
                "WHERE e.type = :type ".
                "AND e.aggregateIdentifier = :aggregateIdentifier ".
                "AND e.scn <= :scnOfFirstToPrune"
            )
                ->setParameters(
                    array(
                        ':type' => $type,
                        ':aggregateIdentifier' => $mostRecentSnapshotEvent->getAggregateIdentifier(),
                        ':scnOfFirstToPrune' => $scnOfFirstToPrune
                    )
                )->execute();
        }
    }

    private function findRedundantSnapshots(
        $type,
        DomainEventMessageInterface $snapshotEvent,
        $maxSnapshotsArchived,
        EntityManager $entityManager
    ) {
        $query = $entityManager->createQuery(
            "SELECT e.scn FROM ".$this->snapshotEventEntryEntityName()." e ".
            "WHERE e.type = :type AND e.aggregateIdentifier = :aggregateIdentifier ".
            "ORDER BY e.scn DESC"
        )
            ->setFirstResult($maxSnapshotsArchived - 1)
            ->setMaxResults(1)
            ->setParameters(
                array(':type' => $type, ':aggregateIdentifier' => $snapshotEvent->getAggregateIdentifier())
            );

        return $query->getResult();
    }

}
