<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Serializer;

use Governor\Framework\Domain\MetaData;

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

    /**
     * @param string $eventIdentifier
     * @param string $aggregateIdentifier
     * @param int $scn
     * @param \DateTime $timestamp
     * @param string $payloadType
     * @param string $payloadRevision
     * @param string $payload
     * @param string $metaData
     */
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
            new SimpleSerializedType(MetaData::class)
        );
    }

    /**
     * @return string
     */
    public function getAggregateIdentifier()
    {
        return $this->aggregateIdentifier;
    }

    /**
     * @return string
     */
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

    /**
     * @return int
     */
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
