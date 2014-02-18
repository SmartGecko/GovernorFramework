<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\EventStore\Filesystem;

use Governor\Framework\Domain\DomainEventStreamInterface;
use Governor\Framework\Serializer\SerializerInterface;

/**
 * Description of FilesystemDomainEventStream
 *
 * @author 255196
 */
class FilesystemDomainEventStream implements DomainEventStreamInterface
{

    private $eventReader;
    private $events;

    public function __construct(\SplFileObject $file,
            SerializerInterface $serializer)
    {
        $this->eventReader = new FilesystemEventMessageReader($file, $serializer);
        $this->events = new \SplDoublyLinkedList();
        $this->doReadNext();
    }

    public function hasNext()
    {
        if ($this->events->isEmpty()) {
            $this->doReadNext();
        }

        return !$this->events->isEmpty();
    }

    public function next()
    {
        $nextMessage = $this->events->shift();

        if ($this->events->isEmpty()) {
            $this->doReadNext();
        }

        return $nextMessage;
    }

    public function peek()
    {
        return $this->events->top();
    }

    private function doReadNext()
    {
        if (null !== $event = $this->eventReader->readEventMessage()) {
            $this->events->push($event);
        }
    }

}
