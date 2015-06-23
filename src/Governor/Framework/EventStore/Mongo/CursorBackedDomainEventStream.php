<?php
/**
 * This file is part of the SmartGecko(c) business platform.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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