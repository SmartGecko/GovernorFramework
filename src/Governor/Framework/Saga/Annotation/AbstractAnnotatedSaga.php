<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Saga\Annotation;

use Rhumsaa\Uuid\Uuid;
use Governor\Framework\Domain\EventMessageInterface;
use Governor\Framework\Saga\SagaInterface;
use Governor\Framework\Saga\AssociationValue;
use Governor\Framework\Saga\Annotation\AssociationValuesImpl;

/**
 * Implementation of the {@link Saga interface} that delegates incoming events to SagaEventHandler annotated methods.
 */
abstract class AbstractAnnotatedSaga implements SagaInterface, \Serializable
{

    /**
     * @var AssociationValuesInterface 
     */
    private $associationValues;

    /**
     * @var string 
     */
    private $identifier;

    /**
     * @var boolean 
     */
    private $isActive = true;

    /**
     *
     * @var SagaMehtodMessageHandlerInspector
     */
    private $inspector;

    /**
     * Initialize the saga. If an identifier is provided it will be used, otherwise a random UUID will be generated.
     * 
     * @param string $identifier
     */
    public function __construct($identifier = null)
    {
        $this->identifier = (null === $identifier) ? Uuid::uuid1()->toString() : $identifier;
        $this->associationValues = new AssociationValuesImpl();
        $this->inspector = new SagaMethodMessageHandlerInspector($this);
        $this->associationValues->add(new AssociationValue('sagaIdentifier',
                $this->identifier));
    }

    public function getSagaIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @return AssociationValuesInterface
     */
    public function getAssociationValues()
    {
        return $this->associationValues;
    }

    public final function handle(EventMessageInterface $event)
    {
        if ($this->isActive()) {
            // find and invoke handler
            $handler = $this->inspector->findHandlerMethod($this, $event);
            $handler->invoke($this, $event);

            if ($handler->isEndingHandler()) {
                $this->end();
            }
        }
    }

    /**
     * @return boolean
     */
    public function isActive()
    {
        return $this->isActive;
    }

    /**
     * Marks the saga as ended. Ended saga's may be cleaned up by the repository when they are committed.
     */
    protected function end()
    {
        $this->isActive = false;
    }

    /**
     * Registers a AssociationValue with the given saga. When the saga is committed, it can be found using the
     * registered property.
     *
     * @param AssociationValue $associationValue The value to associate this saga with.
     */
    public function associateWith(AssociationValue $associationValue)
    {
        $this->associationValues->add($associationValue);
    }

    /**
     * Removes the given association from this Saga. When the saga is committed, it can no longer be found using the
     * given association. If the given property wasn't registered with the saga, nothing happens.
     *
     * @param AssociationValue $property the association value to remove from the saga.
     */
    protected function removeAssociationWith(AssociationValue $associationValue)
    {
        $this->associationValues->remove($associationValue);
    }

    public function serialize()
    {
        return serialize(array('associationValues' => $this->associationValues,
            'identifier' => $this->identifier, 'isActive' => $this->isActive));
    }

    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        $this->associationValues = $data['associationValues'];
        $this->identifier = $data['identifier'];
        $this->isActive = $data['isActive'];
        $this->inspector = new SagaMethodMessageHandlerInspector($this);
    }

}
