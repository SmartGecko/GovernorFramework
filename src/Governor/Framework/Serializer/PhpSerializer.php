<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Serializer;

/**
 * Description of PhpSerializer
 *
 * @author david
 */
class PhpSerializer extends AbstractSerializer
{

    public function deserialize(SerializedObjectInterface $data)
    {        
        return unserialize($data->getData());
    }

    public function serialize($object)
    {
        $result = serialize($object);        
        return new SimpleSerializedObject($result, $this->typeForClass($object));        
    }

}
