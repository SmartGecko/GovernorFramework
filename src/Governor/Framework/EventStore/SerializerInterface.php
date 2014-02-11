<?php

namespace Governor\Framework\EventStore;

use Governor\Framework\DomainEvent;

/**
 * Abstraction for DomainEvent serializers
 */
interface SerializerInterface
{
    public function serialize(DomainEvent $event, $format);
    public function deserialize($eventClass, $data, $format);
}

