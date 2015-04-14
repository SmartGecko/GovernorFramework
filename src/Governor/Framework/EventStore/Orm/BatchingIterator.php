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
use Governor\Framework\Serializer\SerializedDomainEventDataInterface;

/**
 * Description of BatchingIterator
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
class BatchingIterator implements \Iterator
{

    /**
     * @var integer
     */
    private $currentBatchSize;

    /**
     * @var \Iterator
     */
    private $currentBatch;

    /**
     * @var SerializedDomainEventDataInterface
     */
    private $next;

    /**
     * @var SerializedDomainEventDataInterface
     */
    private $lastItem;

    /**
     * @var string
     */
    private $whereClause;

    /**
     * @var array
     */
    private $parameters = array();

    /**
     * @var integer
     */
    private $batchSize;

    /**
     * @var string
     */
    private $domainEventEntryEntityName;

    /**
     * @var EntityManager
     */
    private $em;


    /**
     * @param string $whereClause
     * @param array $parameters
     * @param int $batchSize
     * @param string $domainEventEntryEntityName
     * @param EntityManager $em
     */
    public function __construct(
        $whereClause,
        array $parameters,
        $batchSize,
        $domainEventEntryEntityName,
        EntityManager $em
    ) {
        $this->whereClause = $whereClause;
        $this->parameters = $parameters;
        $this->batchSize = $batchSize;
        $this->domainEventEntryEntityName = $domainEventEntryEntityName;
        $this->em = $em;
        $firstBatch = $this->fetchBatch();

        $this->currentBatchSize = count($firstBatch);
        $this->currentBatch = new \ArrayIterator($firstBatch);

        if ($this->currentBatch->valid()) {
            $this->next = $this->currentBatch->current();
        }
    }

    private function fetchBatch()
    {
        $query = $this->em->createQuery(
            sprintf(
                "SELECT new Governor\Framework\Serializer\SimpleSerializedDomainEventData(".
                "e.eventIdentifier, e.aggregateIdentifier, e.scn, ".
                "e.timestamp, e.payloadType, e.payloadRevision, e.payload, e.metaData) ".
                "FROM %s e %s ORDER BY e.timestamp ASC, "."e.scn ASC, e.aggregateIdentifier ASC",
                $this->domainEventEntryEntityName,
                $this->buildWhereClause()
            )
        )
            ->setMaxResults($this->batchSize)
            ->setParameters($this->parameters);

        $result = $query->getResult();
        $this->lastItem = end($result);
        reset($result);

        return $result;
    }


    /**
     * @return string
     */
    private function buildWhereClause()
    {
        if (null === $this->lastItem && empty($this->whereClause)) {
            return '';
        }

        $query = "WHERE ";
        if (null !== $this->lastItem) {
            $query .= "((e.timestamp > :timestamp) OR (e.timestamp = :timestamp AND e.scn > :scn) ".
                " OR (e.timestamp = :timestamp AND e.scn = :scn AND e.aggregateIdentifier > :aggregateIdentifier))";

            $this->parameters[':timestamp'] = $this->lastItem->getTimestamp();
            $this->parameters[':scn'] = $this->lastItem->getScn();
            $this->parameters[':aggregateIdentifier'] = $this->lastItem->getAggregateIdentifier();
        }

        if (null !== $this->whereClause && strlen($this->whereClause) > 0) {
            if (null !== $this->lastItem) {
                $query .= " AND (";
            }

            $query .= $this->whereClause;

            if (null !== $this->lastItem) {
                $query .= ")";
            }
        }

        return $query;
    }

    public function current()
    {
        return $this->next;
    }

    public function key()
    {

    }

    public function next()
    {
        $this->currentBatch->next();
        $this->next = $this->currentBatch->current();

        if (null === $this->next && $this->currentBatchSize >= $this->batchSize) {
            $entries = $this->fetchBatch();

            $this->currentBatchSize = count($entries);
            $this->currentBatch = new \ArrayIterator($entries);

            if ($this->currentBatch->valid()) {
                $this->next = $this->currentBatch->current();
            }
        }
    }

    public function rewind()
    {
        throw new \BadMethodCallException("BatchingIterator does not support rewind");
    }


    /**
     * @return bool
     */
    public function valid()
    {
        return null !== $this->next;
    }

}
