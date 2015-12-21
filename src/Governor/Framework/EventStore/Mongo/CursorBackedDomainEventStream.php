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

namespace Governor\Framework\EventStore\Mongo;


use Governor\Framework\Domain\DomainEventStreamInterface;
use Governor\Framework\Domain\DomainEventMessageInterface;

class CursorBackedDomainEventStream implements DomainEventStreamInterface
{
    /**
     * @var \Iterator
     */
    private $messagesToReturn;

    /**
     * @var DomainEventMessageInterface
     */
    private $next;

    /**
     * @var \MongoCursor
     */
    private $dbCursor;

    /**
     * @var string
     */
    private $actualAggregateIdentifier;

    /**
     * @var int
     */
    private $lastSequenceNumber;

    /**
     * @var bool
     */
    private $skipUnknownTypes;

    /**
     * @var \Closure
     */
    private $callback;


    /**
     * Initializes the DomainEventStream, streaming events obtained from the given <code>dbCursor</code> and
     * optionally the given <code>lastSnapshotEvent</code>, which stops streaming once an event with a sequence
     * number higher given than <code>lastSequenceNumber</code>.
     *
     * @param \MongoCursor $dbCursor The cursor providing access to the query results in the Mongo instance
     * @param DomainEventMessageInterface[] $lastSnapshotCommit The last snapshot event read, or <code>null</code> if no snapshot is
     *                                  available
     * @param string $actualAggregateIdentifier The actual aggregateIdentifier instance used to perform the lookup, or
     *                                  <code>null</code> if unknown
     * @param int $lastSequenceNumber The highest sequence number this stream may return before indicating
     *                                  end-of-stream
     * @param bool $skipUnknownTypes Whether or not the stream should ignore events that cannot be deserialized
     * @param \Closure $callback
     */
    public function __construct(
        \MongoCursor $dbCursor,
        array $lastSnapshotCommit = [],
        $actualAggregateIdentifier,
        $lastSequenceNumber,
        $skipUnknownTypes,
        \Closure $callback
    ) {
        $this->dbCursor = $dbCursor;
        $this->actualAggregateIdentifier = $actualAggregateIdentifier;
        $this->lastSequenceNumber = null === $lastSequenceNumber ? PHP_INT_MAX : $lastSequenceNumber;
        $this->skipUnknownTypes = $skipUnknownTypes;
        $this->callback = $callback;

        $this->messagesToReturn =  new \ArrayIterator($lastSnapshotCommit);

        $this->initializeNextItem();
    }


    public function hasNext()
    {
        return null !== $this->next && $this->next->getScn() <= $this->lastSequenceNumber;
    }


    public function next()
    {
        $itemToReturn = $this->next;
        $this->initializeNextItem();

        return $itemToReturn;
    }


    public function peek()
    {
        return $this->next;
    }

    /**
     * Ensures that the <code>next</code> points to the correct item, possibly reading from the dbCursor.
     */
    private function initializeNextItem()
    {
        while (!$this->messagesToReturn->valid() && $this->dbCursor->hasNext()) {
            $cb = $this->callback;
            $this->messagesToReturn = new \ArrayIterator(
                $cb($this->dbCursor->getNext(), $this->actualAggregateIdentifier)
            );
        }

        $this->next = $this->messagesToReturn->current();
        $this->messagesToReturn->next();
    }


    public function close()
    {

    }
}