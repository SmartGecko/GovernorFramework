<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Serializer;

/**
 * Description of SimpleSerializedType
 *
 * @author david
 */
class SimpleSerializedType implements SerializedTypeInterface
{

    private $objectType;
    private $revisionNumber;

    function __construct($objectType, $revisionNumber = null)
    {
        $this->objectType = $objectType;
        $this->revisionNumber = $revisionNumber;
    }

    public function getName()
    {
        return $this->objectType;
    }

    public function getRevision()
    {
        return $this->revisionNumber;
    }

}
