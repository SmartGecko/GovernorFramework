<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\EventStore\Filesystem;

use Governor\Framework\Domain\DomainEventMessageInterface;

/**
 * Description of SnapshotFilesystemDomainEventStream

 */
class SnapshotFilesystemDomainEventStream extends FilesystemDomainEventStream
{

    public function __construct(DomainEventMessageInterface $snapshotEvent,
            \SplFileObject $file,
            \Governor\Framework\Serializer\SerializerInterface $serializer)
    {
        $this->eventReader = new FilesystemEventMessageReader($file, $serializer);
        $this->events = new \SplDoublyLinkedList();
        $this->events->push($snapshotEvent);                
    }

}
