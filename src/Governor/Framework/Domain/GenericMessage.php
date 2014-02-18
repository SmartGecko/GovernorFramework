<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Domain;

use Rhumsaa\Uuid\Uuid;

/**
 * Description of GenericMessage
 *
 * @author david
 */
class GenericMessage implements MessageInterface
{

    private $id;
    private $metadata;
    private $payload;

    public function __construct($payload, MetaData $metadata = null, $id = null)
    {
        $this->id = isset($id) ? $id : Uuid::uuid1();
        $this->metadata = isset($metadata) ? $metadata : MetaData::emptyInstance();
        $this->payload = $payload;
    }

    public function getIdentifier()
    {
        return $this->id;
    }

    /**
     * 
     * @return \Governor\Framework\Domain\MetaData
     */
    public function getMetaData()
    {
        return $this->metadata;
    }

    public function getPayload()
    {
        return $this->payload;
    }

    public function getPayloadType()
    {
        return get_class($this->payload);
    }

    public function andMetaData(array $metadata = array())
    {
        if (empty($metadata)) {
            return $this;
        }

        return new GenericMessage($this->getPayload(),
            $this->getMetaData()->mergeWith($metadata));
    }

    public function withMetaData(array $metadata = array())
    {
        if ($this->getMetaData()->isEqualTo($metadata)) {
            return $this;
        }

        return new GenericMessage($this->getPayload(), new MetaData($metadata));
    }

}
