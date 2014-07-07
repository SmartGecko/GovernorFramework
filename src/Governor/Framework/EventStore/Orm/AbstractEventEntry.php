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

use Doctrine\ORM\Mapping as ORM;
use Governor\Framework\Domain\DomainEventMessageInterface;
use Governor\Framework\Serializer\SerializedObjectInterface;
use Governor\Framework\Serializer\SimpleSerializedObject;
use Governor\Framework\Serializer\SimpleSerializedType;

/**
 * Abstract base class that defines the ORM entry for storing events in the event store.
 * 
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a> 
 * @ORM\MappedSuperclass
 */
abstract class AbstractEventEntry
{

    /**
     * @ORM\Id
     * @ORM\Column(name="type", type="string")
     * @var string
     */
    private $type;

    /**
     * @ORM\Id
     * @ORM\Column(name="aggregate_id", type="string")
     * @var mixed
     */
    private $aggregateIdentifier;

    /**
     * @ORM\Id
     * @ORM\Column(name="scn", type="integer")
     * @var integer
     */
    private $scn;

    /**
     * @ORM\Column(name="event_id", type="string", unique=true)
     * @var string
     */
    private $eventIdentifier;

    /**
     * @ORM\Column(name="created_at", type="datetime")
     * @var \DateTime
     */
    private $timestamp;

    /**
     * @ORM\Column(name="payload_type", type="string")
     * @var string
     */
    private $payloadType;

    /**
     * @ORM\Column(name="payload", type="text")
     * @var mixed
     */
    private $payload;

    /**
     * @ORM\Column(name="payload_revision", type="string", nullable=true)
     * @var string
     */
    private $payloadRevision;

    /**
     * @ORM\Column(name="metadata", type="text")
     * @var mixed
     */
    private $metaData;

    /**
     * Initialize an Event entry for the given <code>event</code>.
     *
     * @param string $type     The type identifier of the aggregate root the event belongs to
     * @param DomainEventMessageInterface $event    The event to store in the EventStore
     * @param SerializedObjectInterface $payload  The serialized payload of the Event
     * @param SerializedObjectInterface $metaData The serialized metaData of the Event
     */
    public function __construct($type, DomainEventMessageInterface $event,
            SerializedObjectInterface $payload,
            SerializedObjectInterface $metaData)
    {
        $this->eventIdentifier = $event->getIdentifier();
        $this->type = $type;
        //$this->payloadType = $payload->getContentType();
        $this->payloadType = $payload->getType()->getName();
        $this->payloadRevision = $payload->getType()->getRevision();
        $this->payload = $payload->getData();
        $this->aggregateIdentifier = $event->getAggregateIdentifier();
        $this->scn = $event->getScn();
        $this->metaData = $metaData->getData();
        $this->timestamp = $event->getTimestamp();
    }

    /**
     * Returns the Aggregate Identifier of the associated event.
     *
     * @return mixed the Aggregate Identifier of the associated event.
     */
    public function getAggregateIdentifier()
    {
        return $this->aggregateIdentifier;
    }

    /**
     * Returns the type identifier of the aggregate.
     *
     * @return string the type identifier of the aggregate.
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Returns the sequence number of the associated event.
     *
     * @return integer the sequence number of the associated event.
     */
    public function getScn()
    {
        return $this->scn;
    }

    /**
     * Returns the time stamp of the associated event.
     *
     * @return \DateTime the time stamp of the associated event.
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    public function getEventIdentifier()
    {
        return $this->eventIdentifier;
    }

    public function getPayload()
    {
        return new SimpleSerializedObject($this->payload,
                new SimpleSerializedType($this->payloadType,
                $this->payloadRevision));
    }

    public function getMetaData()
    {
        return new SimpleSerializedObject($this->metaData,
                new SimpleSerializedType('Governor\Framework\Domain\Metadata'));
    }

}
