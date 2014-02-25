<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Serializer;

/**
 * Description of SimpleSerializedObject
 *
 * @author david
 */
class SimpleSerializedObject implements SerializedObjectInterface
{

    private $data;
    private $serializedType;    

    public function __construct($data, SerializedTypeInterface $serializedType)
    {
        $this->data = $data;       
        $this->serializedType = $serializedType;
    }

    public function getContentType()
    {
        return $this->serializedType->getName();
    }

    public function getData()
    {
        return $this->data;
    }

    public function getType()
    {
        return $this->serializedType;
    }

}
