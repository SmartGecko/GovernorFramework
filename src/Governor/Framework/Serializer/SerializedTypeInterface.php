<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Serializer;

/**
 * Describes the type of a serialized object. This information is used to decide how to deserialize an object.
 */
interface SerializedTypeInterface
{

    /**
     * Returns the name of the serialized type. This may be the class name of the serialized object, or an alias for
     * that name.
     *
     * @return string the name of the serialized type
     */
    public function getName();

    /**
     * Returns the revision identifier of the serialized object. This revision identifier is used by upcasters to
     * decide how to transform serialized objects during deserialization.
     *
     * @return string the revision identifier of the serialized object
     */
    public function getRevision();
}
