<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Saga\Repository\Orm;

use Governor\Framework\Saga\AssociationValue;

/**
 * Description of AssociationValueEntry
 *
 * @author david
 */
class AssociationValueEntry
{

    private $id;
    private $sagaId;
    private $associationKey;
    private $associationValue;
    private $sagaType;

    /**
     * Initialize a new AssociationValueEntry for a saga with given <code>sagaIdentifier</code> and
     * <code>associationValue</code>.
     *
     * @param sagaType         The type of Saga this association value belongs to
     * @param sagaIdentifier   The identifier of the saga
     * @param associationValue The association value for the saga
     */
    public function __construct($sagaType, $sagaIdentifier,
            AssociationValue $associationValue)
    {
        $this->sagaType = $sagaType;
        $this->sagaId = $sagaIdentifier;
        $this->associationKey = $associationValue->getPropertyKey();
        $this->associationValue = $associationValue->getPropertyValue();
    }

    /**
     * Returns the association value contained in this entry.
     *
     * @return the association value contained in this entry
     */
    public function getAssociationValue()
    {
        return new AssociationValue($this->associationKey,
                $this->associationValue);
    }

    /**
     * Returns the Saga Identifier contained in this entry.
     *
     * @return the Saga Identifier contained in this entry
     */
    public function getSagaIdentifier()
    {
        return $this->sagaId;
    }

    /**
     * Returns the type (fully qualified class name) of the Saga this association value belongs to
     *
     * @return the type (fully qualified class name) of the Saga
     */
    public function getSagaType()
    {
        return $this->sagaType;
    }

    /**
     * The unique identifier of this entry.
     *
     * @return the unique identifier of this entry
     */
    public function getId()
    {
        return $this->id;
    }

}
