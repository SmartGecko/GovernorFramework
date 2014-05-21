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

use Governor\Framework\Domain\DomainEventMessageInterface;
use Governor\Framework\Serializer\SerializedObjectInterface;
use Governor\Framework\Serializer\SimpleSerializedObject;
use Governor\Framework\Serializer\SimpleSerializedType;

/**
 * Description of AbstractEventEntry 
*/
abstract class AbstractEventEntry
{
    /**     
     * @var string
     */
    private $type;
    
    /**     
     * @var mixed
     */
    private $aggregateIdentifier;
    
    /**     
     * @var integer
     */
    private $scn;
    
    /**     
     * @var string
     */
    private $eventIdentifier;
    
    /**     
     * @var \DateTime
     */
    private $timestamp;
    
    /**     
     * @var string
     */
    private $payloadType;
    
    /**     
     * @var mixed
     */
    private $payload;
    
    /**     
     * @var string
     */    
    private $payloadRevision;
    
    /**     
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
        SerializedObjectInterface $payload, SerializedObjectInterface $metaData)
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
            new SimpleSerializedType($this->payloadType, $this->payloadRevision));
    }

    public function getMetaData()
    {
        return new SimpleSerializedObject($this->metaData,
            new SimpleSerializedType('Governor\Framework\Domain\Metadata'));
    }

}
