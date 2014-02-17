<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\EventStore\Filesystem;

use Governor\Framework\Repository\ConflictingModificationException;
use Governor\Framework\Domain\DomainEventStreamInterface;
use Governor\Framework\EventStore\EventStoreInterface;
use Governor\Framework\EventStore\SnapshotEventStoreInterface;
use Governor\Framework\Serializer\SerializerInterface;

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
     * 
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
            DomainEventStreamInterface $snapshotEvent)
    {
        
    }

    public function readEvents($type, $identifier)
    {
        
    }

}
