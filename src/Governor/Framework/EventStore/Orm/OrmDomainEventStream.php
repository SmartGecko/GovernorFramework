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

use Governor\Framework\Serializer\SerializerInterface;
use Governor\Framework\Domain\GenericDomainEventMessage;
use Governor\Framework\Domain\DomainEventMessageInterface;
use Governor\Framework\Domain\DomainEventStreamInterface;

/**
 * Description of OrmDomainEventStream
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a> 
 */
class OrmDomainEventStream implements DomainEventStreamInterface
{

    /**
     * @var \Iterator
     */
    private $cursor;

    /**
     * @var DomainEventMessageInterface
     */
    private $next;

    /**
     * @var integer
     */
    private $lastScn;

    /**
     * @var string
     */
    private $aggregateIdentifier;

    /**
     * @var boolean
     */
    private $skipUnknownTypes;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @param SerializerInterface $serializer
     * @param \Iterator $cursor
     * @param string $aggregateIdentifier
     * @param DomainEventMessageInterface $snapshotEvent
     * @param integer $lastScn
     * @param boolean $skipUnknownTypes
     */
    public function __construct(SerializerInterface $serializer,
            \Iterator $cursor, $aggregateIdentifier,
            DomainEventMessageInterface $snapshotEvent = null, $lastScn = null,
            $skipUnknownTypes = true)
    {
        $this->serializer = $serializer;
        $this->aggregateIdentifier = $aggregateIdentifier;
        $this->skipUnknownTypes = $skipUnknownTypes;
        $this->lastScn = (null === $lastScn) ? PHP_INT_MAX : $lastScn;
        $this->cursor = $cursor;

        if (null !== $snapshotEvent) {
            $this->next = $snapshotEvent;
            // skip the event with the same SCN 
            $this->cursor->next();
        } else {
            $this->doGetNext();
        }
    }

    public function hasNext()
    {
        return null !== $this->next && $this->next->getScn() <= $this->lastScn;
    }

    public function next()
    {
        $current = $this->next;
        $this->doGetNext();
        return $current;
    }

    public function peek()
    {
        return $this->next;
    }

    private function doGetNext()
    {
        if ($this->cursor->valid()) {
            $event = $this->cursor->current(); //current($eventRow);
            $payload = $this->serializer->deserialize($event->getPayload());
            $metadata = $this->serializer->deserialize($event->getMetaData());

            $this->next = new GenericDomainEventMessage($event->getAggregateIdentifier(),
                    $event->getScn(), $payload, $metadata,
                    $event->getEventIdentifier(), $event->getTimestamp());
        } else {
            $this->next = null;
        }

        $this->cursor->next();
    }

}
