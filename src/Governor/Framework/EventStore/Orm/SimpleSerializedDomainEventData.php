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

use Governor\Framework\Serializer\SimpleSerializedObject;
use Governor\Framework\Serializer\SimpleSerializedType;
use Governor\Framework\Serializer\SerializedDomainEventDataInterface;

/**
 * Simple implementation of the {@link SerializedDomainEventDataInterface} interface.
 * 
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a> 
 */
class SimpleSerializedDomainEventData implements SerializedDomainEventDataInterface
{

    /**
     * @var string
     */
    private $eventIdentifier;

    /**
     * @var string
     */
    private $aggregateIdentifier;

    /**
     * @var integer
     */
    private $scn;

    /**
     * @var \DateTime
     */
    private $timestamp;

    /**
     * @var SimpleSerializedObject
     */
    private $serializedPayload;

    /**
     * @var SimpleSerializedObject
     */
    private $serializedMetaData;

    /**
     * Initialize an instance using given properties. This constructor assumes the default SerializedType for meta data
     * (name = 'org.axonframework.domain.MetaData' and revision = <em>null</em>).
     * <p/>
     * Note that the given <code>timestamp</code> must be in a format supported by {@link} DateTime#DateTime(Object)}.
     *
     * @param string $eventIdentifier     The identifier of the event
     * @param string $aggregateIdentifier The identifier of the aggregate
     * @param integer $scn      The sequence number of the event
     * @param \DateTime $timestamp           The timestamp of the event (format must be supported by {@link
     *                            DateTime#DateTime(Object)})
     * @param string $payloadType         The type identifier of the serialized payload
     * @param string $payloadRevision     The revision of the serialized payload
     * @param mixed $payload             The serialized representation of the event
     * @param mixed $metaData            The serialized representation of the meta data
     */
    public function __construct($eventIdentifier, $aggregateIdentifier, $scn,
            $timestamp, $payloadType, $payloadRevision, $payload, $metaData)
    {
        $this->eventIdentifier = $eventIdentifier;
        $this->aggregateIdentifier = $aggregateIdentifier;
        $this->scn = $scn;
        $this->timestamp = $timestamp;
        $this->serializedPayload = new SimpleSerializedObject($payload,
                new SimpleSerializedType($payloadType, $payloadRevision));
        $this->serializedMetaData = new SimpleSerializedObject($metaData,
                new SimpleSerializedType('Governor\Framework\Domain\MetaData'));
    }

    public function getEventIdentifier()
    {
        return $this->eventIdentifier;
    }

    public function getAggregateIdentifier()
    {
        return $this->aggregateIdentifier;
    }

    public function getScn()
    {
        return $this->scn;
    }

    public function getTimestamp()
    {
        return $this->timestamp;
    }

    public function getMetaData()
    {
        return $this->serializedMetaData;
    }

    public function getPayload()
    {
        return $this->serializedPayload;
    }

}
