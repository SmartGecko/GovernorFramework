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

    private $aggregateId;
    private $scn;

    public function __construct($aggregateId, $scn, $payload, MetaData $metadata)
    {
        parent::__construct($payload, $metadata);
        $this->aggregateId = $aggregateId;
        $this->scn = $scn;
    }

    public function getAggregateId()
    {
        return $this->aggregateId;
    }

    public function getScn()
    {
        return $this->scn;
    }

    public function andMetaData(array $metadata = array())
    {
        if (empty($metadata)) {
            return $this;
        }

        return new GenericDomainEventMessage($this->getAggregateId(),
            $this->scn, $this->getPayload(),
            $this->getMetaData()->mergeWith($metadata));
    }

    public function withMetaData(array $metadata = array())
    {
        if ($this->getMetaData()->isEqualTo($metadata)) {
            return $this;
        }

        return new GenericDomainEventMessage($this->aggregateId, $this->scn,
            $this->getPayload(), new MetaData($metadata));
    }

}
