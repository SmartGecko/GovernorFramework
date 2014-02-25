<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Serializer;

use Governor\Framework\Domain\MetaData;

/**
 * Description of MessageSerializer
 *
 * @author 255196
 */
class MessageSerializer
{

    private $serializer;

    function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * 
     * @param \Governor\Framework\Domain\MetaData $metadata
     * @return SerializedObjectInterface
     */
    public function serializeMetaData(MetaData $metadata)
    {
        return $this->serializer->serialize($metadata);
    }

    /**
     * 
     * @param mixed $payload
     * @return SerializedObjectInterface
     */
    public function serializePayload($payload)
    {
        return $this->serializer->serialize($payload);
    }

}
