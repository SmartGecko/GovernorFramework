<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\EventStore\Orm;

use Governor\Framework\Serializer\SerializerInterface;
use Governor\Framework\Domain\GenericDomainEventMessage;
use Governor\Framework\Domain\DomainEventMessageInterface;
use Governor\Framework\Domain\DomainEventStreamInterface;

/**
 * Description of OrmDomainEventStream
 *
 * @author 255196
 */
class OrmDomainEventStream implements DomainEventStreamInterface
{

    /**
     * @var \Iterator
     */
    private $cursor;

    /**
     * @var DomainEventMessageInterface
     */
    private $next;

    /**
     * @var integer
     */
    private $lastScn;

    /**
     * @var string
     */
    private $aggregateIdentifier;

    /**
     * @var boolean
     */
    private $skipUnknownTypes;
    
    /**     
     * @var SerializerInterface 
     */
    private $serializer;

    /**
     * @param SerializerInterface $serializer
     * @param \Iterator $cursor
     * @param string $aggregateIdentifier
     * @param DomainEventMessageInterface $snapshotEvent
     * @param integer $lastScn
     * @param boolean $skipUnknownTypes
     */
    public function __construct(SerializerInterface $serializer, \Iterator $cursor, $aggregateIdentifier,
            DomainEventMessageInterface $snapshotEvent = null, $lastScn = null,
            $skipUnknownTypes = true)
    {
        $this->serializer = $serializer;
        $this->aggregateIdentifier = $aggregateIdentifier;
        $this->skipUnknownTypes = $skipUnknownTypes;
        $this->lastScn = (null === $lastScn) ? PHP_INT_MAX : $lastScn;
        $this->cursor = $cursor;        
        
        if (null !== $snapshotEvent) {
            $this->next = $snapshotEvent;
        } else {
            $this->doGetNext();
        }
    }

    public function hasNext()
    {
        return null !== $this->next && $this->next->getScn() <= $this->lastScn;
    }

    public function next()
    {
        $current = $this->next;
        $this->doGetNext();
        return $current;
    }

    public function peek()
    {
        return $this->next;
    }

    private function doGetNext()
    {        
        if (false !== $eventRow = $this->cursor->next()) {               
            $event = current($eventRow);
            $payload = $this->serializer->deserialize($event->getPayload());
            $metadata = $this->serializer->deserialize($event->getMetaData());
            
            $this->next = new GenericDomainEventMessage($event->getAggregateIdentifier(),
                    $event->getScn(), $payload, $metadata,
                    $event->getEventIdentifier(), $event->getTimestamp());                        
        } else {
            $this->next = null;
        }
    }

}
