<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\EventSourcing;

use Governor\Framework\Domain\DomainEventStreamInterface;

/**
 * Description of CapturingEventStream
 *
 * @author 255196
 */
class CapturingEventStream implements DomainEventStreamInterface
{

    /**
     * @var \Governor\Framework\Domain\DomainEventStreamInterface
     */
    private $eventStream;
    private $unseenEvents;
    private $expectedVersion;

    public function __construct(DomainEventStreamInterface $events,
            &$unseenEvents, $expectedVersion)
    {
        $this->eventStream = $events;
        $this->unseenEvents = &$unseenEvents;
        $this->expectedVersion = $expectedVersion;
    }

    public function hasNext()
    {
        return $this->eventStream->hasNext();
    }

    public function next()
    {
        $next = $this->eventStream->next();
        if (null !== $this->expectedVersion && $next->getScn() > $this->expectedVersion) {            
            $this->unseenEvents[] = $next;
        }
        
        return $next;
    }

    public function peek()
    {
        return $this->eventStream->peek();
    }

}
