<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Saga;

/**
 * Description of AssociationValue
 *
 */
class AssociationValue
{

    /**
     * @var string
     */
    private $propertyKey;

    /**
     * @var string
     */
    private $propertyValue;

    /**
     * 
     * @param string $propertyKey
     * @param string $propertyValue
     */
    function __construct($propertyKey, $propertyValue)
    {
        $this->propertyKey = $propertyKey;
        $this->propertyValue = $propertyValue;
    }

    /**
     * @return string
     */
    public function getPropertyKey()
    {
        return $this->propertyKey;
    }

    /**
     * @return string
     */
    public function getPropertyValue()
    {
        return $this->propertyValue;
    }

}
