<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Saga\Annotation;

use JMS\Serializer\Annotation as JMS;
use Governor\Framework\Saga\AssociationValue;
use Governor\Framework\Saga\AssociationValuesInterface;

/**
 * Description of AssociationValuesImpl
 *
 */
class AssociationValuesImpl implements AssociationValuesInterface
{

    /**
     * @JMS\Type ("array<Governor\Framework\Saga\AssociationValue>")
     * @var array
     */
    private $values;

    /**
     * @JMS\Exclude
     * @var array
     */
    private $addedValues;

    /**
     * @JMS\Exclude
     * @var array
     */
    private $removedValues;

    public function __construct()
    {
        $this->values = array();
        $this->addedValues = array();
        $this->removedValues = array();
    }

    /**
     * @JMS\PostDeserialize
     */
    public function postDeserialize()
    {
        $this->addedValues = array();
        $this->removedValues = array();
    }

    /**
     * Searches the array containes an association value identical to the specified one.
     * Elements are compared with <code>==</code> for equality.
     *
     * @param \Governor\Framework\Saga\AssociationValue $associationValue
     * @param array $collection
     * @return boolean
     */
    private function inCollection(
        AssociationValue $associationValue,
        array $collection
    ) {
        foreach ($collection as $element) {
            if ($element == $associationValue) {
                return true;
            }
        }

        return false;
    }

    public function add(AssociationValue $associationValue)
    {
        if (!$this->inCollection($associationValue, $this->values)) {
            $this->values[] = $associationValue;
            $added = true;
        } else {
            $added = false;
        }

        if ($added) {
            if ($this->inCollection($associationValue, $this->removedValues)) {
                $this->removedValues = array_udiff(
                    $this->removedValues,
                    array($associationValue),
                    function ($a, $b) {
                        return $a->compareTo($b);
                    }
                );
            } else {
                $this->addedValues[] = $associationValue;
            }
        }

        return $added;
    }

    public function addedAssociations()
    {
        return $this->addedValues;
    }

    public function commit()
    {
        $this->addedValues = array();
        $this->removedValues = array();
    }

    public function contains(AssociationValue $associationValue)
    {
        return $this->inCollection($associationValue, $this->values);
    }

    public function remove(AssociationValue $associationValue)
    {
        if ($this->inCollection($associationValue, $this->values)) {
            $this->values = array_udiff(
                $this->values,
                array($associationValue),
                function ($a, $b) {
                    return $a->compareTo($b);
                }
            );
            $removed = true;
        } else {
            $removed = false;
        }

        if ($removed) {
            if ($this->inCollection($associationValue, $this->addedValues)) {
                $this->addedValues = array_udiff(
                    $this->addedValues,
                    array($associationValue),
                    function ($a, $b) {
                        return $a->compareTo($b);
                    }
                );
            } else {
                $this->removedValues[] = $associationValue;
            }
        }

        return $removed;
    }

    public function removedAssociations()
    {
        return $this->removedValues;
    }

    public function size()
    {
        return count($this->values);
    }

    public function isEmpty()
    {
        return empty($this->values);
    }

    /**
     * @return AssociationValue[]
     */
    public function asArray()
    {
        return $this->values;
    }


}
