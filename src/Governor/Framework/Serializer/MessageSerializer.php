<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Serializer;

use Governor\Framework\Domain\MessageInterface;
use Governor\Framework\Domain\MetaData;

/**
 * Description of MessageSerializer
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a> 
 */
class MessageSerializer implements SerializerInterface
{

    private $serializer;

    function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * 
     * @param \Governor\Framework\Domain\MessageInterface $message
     * @return SerializedObjectInterface
     */
    public function serializeMetaData(MessageInterface $message)
    {
        return $this->serializer->serialize($message->getMetaData());
    }

    /**
     * 
     * @param \Governor\Framework\Domain\MessageInterface $message
     * @return SerializedObjectInterface
     */
    public function serializePayload(MessageInterface $message)
    {
        return $this->serializer->serialize($message->getPayload());
    }

    public function deserialize(SerializedObjectInterface $data)
    {
        return $this->serializer->deserialize($data);
    }

    public function serialize($object)
    {        
        return $this->serializer->serialize($object);
    }

    public function typeForClass($object)
    {
        return $this->serializer->typeForClass($object);
    }

}
