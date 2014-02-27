<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\EventSourcing;

use Governor\Framework\Domain\DomainEventMessageInterface;

/**
 * Description of GenericAggregateFactory
 *
 * @author david
 */
class GenericAggregateFactory extends AbstractAggregateFactory
{

    /**
     * @var string
     */
    private $aggregateType;

    /**
     * @var string
     */
    private $typeIdentifier;

    /**
     * @var \ReflectionClass
     */
    private $reflClass;

    function __construct($aggregateType)
    {
        $this->reflClass = new \ReflectionClass($aggregateType);

        if (!$this->reflClass->implementsInterface('Governor\Framework\EventSourcing\EventSourcedAggregateRootInterface')) {
            throw new \InvalidArgumentException("The given aggregateType must be a subtype of EventSourcedAggregateRootInterface");
        }

        $this->aggregateType = $aggregateType;
        $this->typeIdentifier = $this->reflClass->getShortName();
    }

    protected function doCreateAggregate($aggregateIdentifier,
        DomainEventMessageInterface $firstEvent)
    {
        return $this->reflClass->newInstanceWithoutConstructor();
    }

    public function getAggregateType()
    {
        return $this->aggregateType;
    }

    public function getTypeIdentifier()
    {
        return $this->typeIdentifier;
    }

}
