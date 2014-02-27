<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Domain;

/**
 * Description of GenericDomainEventMessage
 *
 * @author david
 */
class GenericDomainEventMessage extends GenericEventMessage implements DomainEventMessageInterface
{

    private $aggregateIdentifier;
    private $scn;

    /**
     * 
     * @param string $aggregateIdentifier
     * @param integer $scn
     * @param mixed $payload
     * @param \Governor\Framework\Domain\MetaData $metadata
     * @param string $id
     * @param \DateTime $timestamp
     */
    public function __construct($aggregateIdentifier, $scn, $payload,
            MetaData $metadata = null, $id = null, \DateTime $timestamp = null)
    {
        parent::__construct($payload, $metadata, $id, $timestamp);
        $this->aggregateIdentifier = $aggregateIdentifier;
        $this->scn = $scn;
    }

    public function getAggregateIdentifier()
    {
        return $this->aggregateIdentifier;
    }

    public function getScn()
    {
        return $this->scn;
    }

    /**
     * 
     * @param array $metadata
     * @return \Governor\Framework\Domain\GenericDomainEventMessage
     */
    public function andMetaData(array $metadata = array())
    {
        if (empty($metadata)) {
            return $this;
        }

        return new GenericDomainEventMessage($this->getAggregateIdentifier(),
                $this->scn, $this->getPayload(),
                $this->getMetaData()->mergeWith($metadata));
    }

    /**
     * 
     * @param array $metadata
     * @return \Governor\Framework\Domain\GenericDomainEventMessage
     */
    public function withMetaData(array $metadata = array())
    {
        if ($this->getMetaData()->isEqualTo($metadata)) {
            return $this;
        }

        return new GenericDomainEventMessage($this->aggregateIdentifier,
                $this->scn, $this->getPayload(), new MetaData($metadata));
    }

}
