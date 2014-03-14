<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Saga\Repository\Orm;

use Governor\Framework\Serializer\SerializedTypeInterface;
use Governor\Framework\Serializer\SimpleSerializedObject;

/**
 * Description of SerializedSaga
 *
 * @author david
 */
class SerializedSaga extends SimpleSerializedObject
{

    public function __construct($data, SerializedTypeInterface $serializedType)
    {
        parent::__construct($data, $serializedType);
    }

}
