<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\EventStore\Filesystem;

use Governor\Framework\Serializer\SerializerInterface;
use Governor\Framework\Serializer\SerializedDomainEventDataInterface;

/**
 * Description of SnapshotFilesystemDomainEventStream

 */
class SnapshotFilesystemDomainEventStream extends FilesystemDomainEventStream
{

    /**
     * 
     * @param \Governor\Framework\Serializer\SerializedDomainEventDataInterface $snapshotEvent
     * @param \SplFileObject $file
     * @param \Governor\Framework\Serializer\SerializerInterface $serializer
     */
    public function __construct(SerializedDomainEventDataInterface $snapshotEvent,
        \SplFileObject $file, SerializerInterface $serializer)
    {
        $this->eventReader = new FilesystemEventMessageReader($file, $serializer);
        $this->events = new \SplDoublyLinkedList();
        $this->serializer = $serializer;
        $this->events->push($snapshotEvent);
    }

}
