<?php

namespace Governor\Framework\Serializer;

/**
 * Abstraction for DomainEvent serializers
 */
interface SerializerInterface
{

    /**
     * @return SerializedObjectInterface
     */
    public function serialize($object);

    /**
     * @param SerializedObjectInterface $data     
     * @return mixed
     */
    public function deserialize(SerializedObjectInterface $data);

    /**
     * @return SerializedObjectInterface
     */
    public function typeForClass($object);
}
