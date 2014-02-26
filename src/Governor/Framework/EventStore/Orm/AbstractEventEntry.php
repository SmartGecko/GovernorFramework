<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\EventStore\Orm;

use Governor\Framework\Domain\DomainEventMessageInterface;
use Governor\Framework\Serializer\SerializedObjectInterface;
use Governor\Framework\Serializer\SimpleSerializedObject;
use Governor\Framework\Serializer\SimpleSerializedType;

/**
 * Description of AbstractEventEntry 
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
        SerializedObjectInterface $payload, SerializedObjectInterface $metaData)
    {
        $this->eventIdentifier = $event->getIdentifier();
        $this->type = $type;
        //$this->payloadType = $payload->getContentType();
        $this->payloadType = $payload->getType()->getName();
        $this->payloadRevision = $payload->getType()->getRevision();
        $this->payload = $payload->getData();
        $this->aggregateIdentifier = $event->getAggregateIdentifier();
        $this->scn = $event->getScn();
        $this->metaData = $metaData->getData();
        $this->timestamp = $event->getTimestamp();
    }

    /**
     * Returns the Aggregate Identifier of the associated event.
     *
     * @return the Aggregate Identifier of the associated event.
     */
    public function getAggregateIdentifier()
    {
        return $this->aggregateIdentifier;
    }

    /**
     * Returns the type identifier of the aggregate.
     *
     * @return the type identifier of the aggregate.
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Returns the sequence number of the associated event.
     *
     * @return the sequence number of the associated event.
     */
    public function getScn()
    {
        return $this->scn;
    }

    /**
     * Returns the time stamp of the associated event.
     *
     * @return the time stamp of the associated event.
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    public function getEventIdentifier()
    {
        return eventIdentifier;
    }

    public function getPayload()
    {
        return new SimpleSerializedObject($this->payload,
            new SimpleSerializedType($this->payloadType, $this->payloadRevision));
    }

    public function getMetaData()
    {
        return new SimpleSerializedObject($this->metaData,
            new SimpleSerializedType('Governor\Framework\Domain\Metadata'));
    }

}
