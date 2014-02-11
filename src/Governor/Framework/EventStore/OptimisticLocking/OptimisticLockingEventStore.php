<?php

namespace Governor\Framework\EventStore\OptimisticLocking;

use Rhumsaa\Uuid\Uuid;
use Governor\Framework\EventStore\DomainEventStream;
use Governor\Framework\EventStore\Transaction;
use Governor\Framework\EventStore\EventStreamNotFoundException;
use Governor\Framework\EventStore\EventStoreInterface;
//use Governor\Framework\Serializer\Serializer;
use JMS\Serializer\SerializerInterface;

class OptimisticLockingEventStore implements EventStoreInterface
{
    private $storage;
    private $serializer;
    private $eventsData = array();

    public function __construct(Storage $storage, SerializerInterface $serializer)
    {
        $this->storage = $storage;
        $this->serializer = $serializer;
    }

    /**
     * @throws EventStreamNotFoundException
     * @return DomainEventStream
     */
    public function find(Uuid $uuid)
    {
        $streamData = $this->storage->load((string)$uuid);

        if ($streamData === null) {
            throw new EventStreamNotFoundException();
        }

        $events = array();

        foreach ($streamData->getEventData() as $eventData) {
            $events[] = $this->serializer->fromArray($eventData);
        }

        return new DomainEventStream(
            $streamData->getClassName(),
            Uuid::fromString($streamData->getId()),
            $events,
            $streamData->getVersion()
        );
    }

    /**
     * Commit the event stream to persistence.
     *
     * @return Transaction
     */
    public function commit(DomainEventStream $stream)
    {
        $newEvents = $stream->newEvents();

        if (count($newEvents) === 0) {
            return new Transaction($stream, $newEvents);
        }

        $id = (string)$stream->getUuid();
        $currentVersion = $stream->getVersion();
        $nextVersion = $currentVersion + count($newEvents);

        $eventData = isset($this->eventsData[$id])
            ? $this->eventsData[$id]
            : array();

        foreach ($newEvents as $newEvent) {
            //$eventData[] = $this->serializer->toArray($newEvent);
            $eventData[] = $this->serializer->serialize($newEvent, 'json');
        }

        $this->storage->store($id, $stream->getClassName(), $eventData, $nextVersion, $currentVersion);

        $stream->markNewEventsProcessed($nextVersion);

        return new Transaction($stream, $newEvents);
    }

    public function appendEvents($type,
        \Governor\Framework\Domain\DomainEventStreamInterface $events)
    {
        
    }

    public function readEvents($type, $identifier)
    {
        
    }

}
