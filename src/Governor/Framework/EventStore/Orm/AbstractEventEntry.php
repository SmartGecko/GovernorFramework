<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\EventStore\Orm;

use Governor\Framework\Domain\DomainEventMessageInterface;

/**
 * Description of AbstractEventEntry
 *
 * @author 255196
 */
abstract class AbstractEventEntry
{
    private $type;
    private $aggregateIdentifier;
    private $scn;
    private $eventIdentifier;
    private $timestamp;
    private $payloadType;
    private $payload;    
    private $payloadRevision;
    private $metaData;
    
     /**
     * Initialize an Event entry for the given <code>event</code>.
     *
     * @param type     The type identifier of the aggregate root the event belongs to
     * @param event    The event to store in the EventStore
     * @param payload  The serialized payload of the Event
     * @param metaData The serialized metaData of the Event
     */
    public function __construct($type, DomainEventMessageInterface $event,
                                 $payload, $metaData) {
       $this->eventIdentifier = $event->getIdentifier();
        $this->type = $type;
        //$this->payloadType = $payload.getType().getName();
      //  $this->payloadRevision = payload.getType().getRevision();
       // $this->payload = payload.getData();
        $this->aggregateIdentifier = $event->getAggregateIdentifier();
        $this->sequenceNumber = $event->getScn();
        $this->metaData = $metaData;
        $this->timeStamp = $event->getTimestamp();
    }
}
