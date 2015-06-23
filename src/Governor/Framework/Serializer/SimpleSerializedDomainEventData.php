<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Serializer;

/**
 * Description of SimpleSerializedDomainEventData
 *
 * @author david
 */
class SimpleSerializedDomainEventData implements SerializedDomainEventDataInterface
{

    /**
     * @var string
     */
    private $eventIdentifier;

    /**
     * @var string
     */
    private $aggregateIdentifier;

    /**
     * @var integer
     */
    private $scn;

    /**
     * @var \DateTime
     */
    private $timestamp;

    /**
     * @var SerializedObjectInterface
     */
    private $serializedPayload;

    /**
     * @var SerializedObjectInterface
     */
    private $serializedMetaData;

    public function __construct(
        $eventIdentifier,
        $aggregateIdentifier,
        $scn,
        \DateTime $timestamp,
        $payloadType,
        $payloadRevision,
        $payload,
        $metaData
    ) {
        $this->eventIdentifier = $eventIdentifier;
        $this->aggregateIdentifier = $aggregateIdentifier;
        $this->scn = $scn;
        $this->timestamp = $timestamp;
        $this->serializedPayload = new SimpleSerializedObject(
            $payload,
            new SimpleSerializedType($payloadType, $payloadRevision)
        );
        $this->serializedMetaData = new SimpleSerializedObject(
            $metaData,
            new SimpleSerializedType('Governor\Framework\Domain\MetaData')
        );
    }

    public function getAggregateIdentifier()
    {
        return $this->aggregateIdentifier;
    }

    public function getEventIdentifier()
    {
        return $this->eventIdentifier;
    }

    /**
     * @return SerializedObjectInterface
     */
    public function getMetaData()
    {
        return $this->serializedMetaData;
    }

    /**
     * @return SerializedObjectInterface
     */
    public function getPayload()
    {
        return $this->serializedPayload;
    }

    public function getScn()
    {
        return $this->scn;
    }

    /**
     *
     * @return \DateTime
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

}
