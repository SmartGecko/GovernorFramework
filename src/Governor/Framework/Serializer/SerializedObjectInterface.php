<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Serializer;

/**
 * Interface describing the structure of a serialized object.
 */
interface SerializedObjectInterface
{

    /**
     * Returns the type of this representation's data.
     *
     * @return the type of this representation's data
     */
    public function getContentType();

    /**
     * Returns the description of the type of object contained in the data.
     *
     * @return SerializedTypeInterface the description of the type of object contained in the data
     */
    public function getType();

    /**
     * The actual data of the serialized object.
     *
     * @return mixed the actual data of the serialized object
     */
    public function getData();
}
