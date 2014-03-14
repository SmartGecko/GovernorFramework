<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Saga;

class SagaInitializationPolicy
{

    //public static final SagaInitializationPolicy NONE = new SagaInitializationPolicy(SagaCreationPolicy.NONE, null);

    private $creationPolicy;
    private $initialAssociationValue;

    /**
     * Creates an instance using the given <code>creationPolicy</code> and <code>initialAssociationValue</code>. To
     * indicate that no saga should be created, use {@link #NONE} instead of this constructor.
     *
     * @param integer $creationPolicy          The policy describing the condition to create a new instance
     * @param AssociationValue $initialAssociationValue The association value a new Saga instance should be given
     */
    public function __construct($creationPolicy, AssociationValue $initialAssociationValue = null)
    {
        $this->creationPolicy = $creationPolicy;
        $this->initialAssociationValue = $initialAssociationValue;
    }

    /**
     * Returns the creation policy
     *
     * @return integer the creation policy
     */
    public function getCreationPolicy()
    {
        return $this->creationPolicy;
    }

    /**
     * Returns the initial association value for a newly created saga. May be <code>null</code>.
     *
     * @return AssociationValue the initial association value for a newly created saga
     */
    public function getInitialAssociationValue()
    {
        return $this->initialAssociationValue;
    }

}
