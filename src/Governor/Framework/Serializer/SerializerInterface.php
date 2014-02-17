<?php

namespace Governor\Framework\Serializer;


/**
 * Abstraction for DomainEvent serializers
 */
interface SerializerInterface
{
    public function serialize($object);
    public function deserialize($data);
}

