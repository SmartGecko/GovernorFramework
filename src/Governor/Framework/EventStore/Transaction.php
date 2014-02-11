<?php

namespace Governor\Framework\EventStore;

class Transaction
{
    private $eventStream;
    private $committedEvents = array();

    public function __construct(DomainEventStream $eventStream, array $committedEvents)
    {
        $this->eventStream = $eventStream;
        $this->committedEvents = $committedEvents;
    }

    /**
     * @return \Governor\Framework\EventStore\DomainEventStream
     */
    public function getEventStream()
    {
        return $this->eventStream;
    }

    /**
     * @return array<Governor\Framework\DomainEvent>
     */
    public function getCommittedEvents()
    {
        return $this->committedEvents;
    }
}
