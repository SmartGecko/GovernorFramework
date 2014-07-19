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
 * The BatchingAggregateStreamIterator iterates over the event stream in batches.
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a> 
 */
class BatchingAggregateStreamIterator implements \Iterator
{

    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $typeId;

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
     * @var integer
     */
    private $scn;

    public function __construct($firstScn, $id, $typeId, $batchSize,
            $domainEventEntryEntityName, EntityManager $em)
    {
        $this->id = $id;
        $this->typeId = $typeId;
        $this->batchSize = $batchSize;
        $this->domainEventEntryEntityName = $domainEventEntryEntityName;
        $this->em = $em;

        $firstBatch = $this->fetchBatch($firstScn);
        $this->currentBatchSize = count($firstBatch);
        $this->currentBatch = new \ArrayIterator($firstBatch);

        if ($this->currentBatch->valid()) {
            $this->next = $this->currentBatch->current();
            $this->scn = $this->next->getScn();
        }
    }

    private function fetchBatch($firstScn)
    {
        $query = $this->em->createQuery(
                        "SELECT new Governor\Framework\Serializer\SimpleSerializedDomainEventData(" .
                        "e.eventIdentifier, e.aggregateIdentifier, e.scn, " .
                        "e.timestamp, e.payloadType, e.payloadRevision, e.payload, e.metaData) " .
                        "FROM " . $this->domainEventEntryEntityName . " e " .
                        "WHERE e.aggregateIdentifier = :id AND e.type = :type " .
                        "AND e.scn >= :seq " .
                        "ORDER BY e.scn ASC")
                ->setMaxResults($this->batchSize)
                ->setParameters(array(':id' => $this->id, ':type' => $this->typeId,
            ':seq' => $firstScn));

        return $query->getResult();
    }

    public function current()
    {
        $this->scn = $this->next->getScn();
        return $this->next;
    }

    public function key()
    {
        return $this->position;
    }

    public function next()
    {
        $this->currentBatch->next();
        $this->next = $this->currentBatch->current();

        if (null === $this->next && $this->currentBatchSize >= $this->batchSize) {
            $entries = $this->fetchBatch($this->scn + 1);
         
            $this->currentBatchSize = count($entries);
            $this->currentBatch = new \ArrayIterator($entries);

            if ($this->currentBatch->valid()) {
                $this->next = $this->currentBatch->current();
                $this->scn = $this->next->getScn();
            }
        }
    }

    public function rewind()
    {
        throw new \BadMethodCallException("BatchingAggregateStreamIterator does not support rewind");
    }

    public function valid()
    {
        return null !== $this->next;
    }

}
