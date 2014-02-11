<?php

namespace Governor\Framework\Serializer;

interface Serializer
{
    public function fromArray(array $data);
    public function toArray($object);
}
