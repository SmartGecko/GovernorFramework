<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Saga;

use JMS\Serializer\Annotation\Type;
use Doctrine\Common\Comparable;

/**
 * Description of AssociationValue
 *
 */
class AssociationValue implements Comparable
{

    /**
     * @Type ("string")
     * @var string
     */
    private $propertyKey;

    /**
     * @Type ("string")
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

    /**
     * {@inheritdoc}
     */
    public function compareTo($other)
    {
        if (0 !== $keyDiff = strcmp($this->propertyKey, $other->propertyKey)) {
            return $keyDiff;
        }

        if (0 !== $valDiff = strcmp($this->propertyValue, $other->propertyValue)) {
            return $valDiff;
        }

        return 0;
    }

}
