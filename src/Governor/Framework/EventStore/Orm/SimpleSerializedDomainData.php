<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\EventStore\Orm;

use Governor\Framework\Serializer\SimpleSerializedObject;
use Governor\Framework\Serializer\SimpleSerializedType;
use Governor\Framework\Serializer\SerializedDomainEventDataInterface;

/**
 * Simple implementation of the {@link SerializedDomainEventData} class, used to reduce memory consumptions by queries
 * accessing Event Entries. Querying from them directly will cause the EntityManager to keep a reference to them,
 * preventing them from being garbage collected.
 */
class SimpleSerializedDomainEventData implements SerializedDomainEventDataInterface
{

    private $eventIdentifier;
    private $aggregateIdentifier;
    private $scn;
    private $timestamp;
    private $serializedPayload;
    private $serializedMetaData;

    /**
     * Initialize an instance using given properties. This constructor assumes the default SerializedType for meta data
     * (name = 'org.axonframework.domain.MetaData' and revision = <em>null</em>).
     * <p/>
     * Note that the given <code>timestamp</code> must be in a format supported by {@link} DateTime#DateTime(Object)}.
     *
     * @param eventIdentifier     The identifier of the event
     * @param aggregateIdentifier The identifier of the aggregate
     * @param sequenceNumber      The sequence number of the event
     * @param timestamp           The timestamp of the event (format must be supported by {@link
     *                            DateTime#DateTime(Object)})
     * @param payloadType         The type identifier of the serialized payload
     * @param payloadRevision     The revision of the serialized payload
     * @param payload             The serialized representation of the event
     * @param metaData            The serialized representation of the meta data
     */
    public function __construct($eventIdentifier, $aggregateIdentifier, $scn,
        $timestamp, $payloadType, $payloadRevision, $payload, $metaData)
    {
        $this->eventIdentifier = $eventIdentifier;
        $this->aggregateIdentifier = $aggregateIdentifier;
        $this->scn = $scn;
        $this->timestamp = $timestamp;
        $this->serializedPayload = new SimpleSerializedObject($payload,
            new SimpleSerializedType($payloadType, $payloadRevision));
        $this->serializedMetaData = new SimpleSerializedObject($metaData,
            new SimpleSerializedType('Governor\Framework\Domain\MetaData'));
    }

    public function getEventIdentifier()
    {
        return $this->eventIdentifier;
    }

    public function getAggregateIdentifier()
    {
        return $this->aggregateIdentifier;
    }

    public function getScn()
    {
        return $this->scn;
    }

    public function getTimestamp()
    {
        return $this->timestamp;
    }

    public function getMetaData()
    {
        return $this->serializedMetaData;
    }

    public function getPayload()
    {
        return $this->serializedPayload;
    }

}
