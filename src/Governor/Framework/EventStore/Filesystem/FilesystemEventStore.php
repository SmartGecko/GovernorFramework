<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\EventStore\Filesystem;

use Psr\Log\LoggerInterface;
use Governor\Framework\Repository\ConflictingModificationException;
use Governor\Framework\Domain\DomainEventStreamInterface;
use Governor\Framework\Domain\DomainEventMessageInterface;
use Governor\Framework\EventStore\EventStoreInterface;
use Governor\Framework\EventStore\SnapshotEventStoreInterface;
use Governor\Framework\Serializer\SerializerInterface;
use Governor\Framework\EventStore\EventStreamNotFoundException;

/**
 * Description of FilesystemEventStore
 *
 */
class FilesystemEventStore implements EventStoreInterface, SnapshotEventStoreInterface
{

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     *
     * @var EventFileResolverInterface 
     */
    private $fileResolver;
    
    /**     
     * @var LoggerInterface
     */
    private $logger;

    /**
     *
     * @param EventFileResolverInterface $fileResolver
     * @param SerializerInterface $serializer
     */
    function __construct(EventFileResolverInterface $fileResolver,
            SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
        $this->fileResolver = $fileResolver;
    }

    public function appendEvents($type, DomainEventStreamInterface $events)
    {
        if (!$events->hasNext()) {
            return;
        }

        $next = $events->peek();
        if (0 === $next->getScn() && $this->fileResolver->eventFileExists($type,
                        $next->getAggregateIdentifier())) {
            throw new ConflictingModificationException(sprintf("Could not create event stream for aggregate, such stream "
                    . "already exists, type=%s, id=%s", $type,
                    $next->getAggregateIdentifier()));
        }

        $file = $this->fileResolver->openEventFileForWriting($type,
                $next->getAggregateIdentifier());
        $eventMessageWriter = new FilesystemEventMessageWriter($file,
                $this->serializer);

        while ($events->hasNext()) {
            $eventMessageWriter->writeEventMessage($events->next());
        }
    }

    public function appendSnapshotEvent($type,
            DomainEventMessageInterface $snapshotEvent)
    {
        $eventFile = $this->fileResolver->openEventFileForReading($type,
                $snapshotEvent->getAggregateIdentifier());
        $snapshotEventFile = $this->fileResolver->openSnapshotFileForWriting($type,
                $snapshotEvent->getAggregateIdentifier());

        $snapshotEventWriter = new FilesystemSnapshotEventWriter($eventFile,
                $snapshotEventFile, $this->serializer);

        $snapshotEventWriter->writeSnapshotEvent($snapshotEvent);
    }

    public function readEvents($type, $identifier)
    {
        if (!$this->fileResolver->eventFileExists($type, $identifier)) {
            throw new EventStreamNotFoundException($type, $identifier);
        }

        $file = $this->fileResolver->openEventFileForReading($type, $identifier);

        try {
            $snapshotEvent = $this->readSnapshotEvent($type, $identifier, $file);
        } catch (\Exception $ex) {
            $snapshotEvent = null;
        }

        if (null !== $snapshotEvent) {            
            return new SnapshotFilesystemDomainEventStream($snapshotEvent,
                    $file, $this->serializer);
        }

        return new FilesystemDomainEventStream($file, $this->serializer);
    }

    private function readSnapshotEvent($type, $identifier, $eventFile)
    {
        $snapshotEvent = null;
        if ($this->fileResolver->snapshotFileExists($type, $identifier)) {
            $snapshotEventFile = $this->fileResolver->openSnapshotFileForReading($type,
                    $identifier);
            $fileSystemSnapshotEventReader = new FilesystemSnapshotEventReader($eventFile,
                    $snapshotEventFile, $this->serializer);
            $snapshotEvent = $fileSystemSnapshotEventReader->readSnapshotEvent($type,
                    $identifier);
        }
        return $snapshotEvent;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

}
