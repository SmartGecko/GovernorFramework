<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Saga\Annotation;

use Doctrine\Common\Comparable;
use Doctrine\Common\Annotations\AnnotationReader;
use Governor\Framework\Saga\AssociationValue;
use Governor\Framework\Domain\EventMessageInterface;
use Governor\Framework\Saga\SagaCreationPolicy;
use Governor\Framework\Annotations\SagaEventHandler;
use Governor\Framework\Common\Property\PropertyAccessStrategy;

/**
 * Description of SagaMethodMessageHandler
 *
 * @author david
 */
class SagaMethodMessageHandler implements Comparable
{

    /**
     * @var AnnotationReader
     */
    private $reader;
    private $creationPolicy;
    private $handlerMethod;
    private $associationKey;

    /**
     *
     * @var  Governor\Framework\Common\Property\PropertyInterface
     */
    private $associationProperty;

    public static function noHandlers()
    {
        return new SagaMethodMessageHandler(SagaCreationPolicy::NONE, null,
            null, null, null);
    }

    public static function getInstance(EventMessageInterface $event,
        \ReflectionMethod $handlerMethod)
    {
        $reader = new AnnotationReader();
        $handlerAnnotation = $reader->getMethodAnnotation($handlerMethod,
            'Governor\Framework\Annotations\SagaEventHandler');

        $associationProperty = PropertyAccessStrategy::getProperty($event->getPayload(),
                $handlerAnnotation->associationProperty);


        if (null === $associationProperty) {
            throw new \RuntimeException(sprintf("SagaEventHandler %s.%s defines a property %s that is not " + "defined on the Event it declares to handle (%s)",
                $handlerMethod->class, $handlerMethod->name,
                $handlerAnnotation->associationProperty,
                $event->getPayloadType()));
        }

        $associationKey = (empty($handlerAnnotation->keyName)) ? $handlerAnnotation->associationProperty : $handlerAnnotation->keyName;
        $startAnnotation = $reader->getMethodAnnotation($handlerMethod,
            'Governor\Framework\Annotations\StartSaga');

        if (null === $startAnnotation) {
            $sagaCreationPolicy = SagaCreationPolicy::NONE;
        } else if ($startAnnotation->forceNew) {
            $sagaCreationPolicy = SagaCreationPolicy::ALWAYS;
        } else {
            $sagaCreationPolicy = SagaCreationPolicy::IF_NONE_FOUND;
        }

        return new SagaMethodMessageHandler($sagaCreationPolicy,
            $associationKey, $associationProperty, $handlerMethod, $reader);
    }

    private function __construct($creationPolicy, $associationKey,
        $associationProperty, \ReflectionMethod $method = null,
        AnnotationReader $reader = null)
    {
        $this->reader = $reader;
        $this->creationPolicy = $creationPolicy;
        $this->handlerMethod = $method;
        $this->associationKey = $associationKey;
        $this->associationProperty = $associationProperty;
    }

    public function getCreationPolicy()
    {
        return $this->creationPolicy;
    }

    /**
     * Indicates whether the inspected method is an Event Handler.
     *
     * @return true if the saga has a handler
     */
    public function isHandlerAvailable()
    {
        return null !== $this->handlerMethod;
    }

    public function isEndingHandler()
    {
        return $this->isHandlerAvailable() && null !== $this->reader->getMethodAnnotation($this->handlerMethod,
                'Governor\Framework\Annotations\EndSaga');
    }

    public function invoke($target, EventMessageInterface $event)
    {
        if (!$this->isHandlerAvailable()) {
            return;
        }

        $this->handlerMethod->setAccessible(true);
        $this->handlerMethod->invokeArgs($target, array($event->getPayload()));
    }

    /**
     * The AssociationValue to find the saga instance with, or <code>null</code> if no AssociationValue can be found on
     * the given <code>eventMessage</code>.
     *
     * @param eventMessage The event message containing the value of the association
     * @return the AssociationValue to find the saga instance with, or <code>null</code> if none found
     */
    public function getAssociationValue(EventMessageInterface $eventMessage)
    {
        if (null === $this->associationProperty) {
            return null;
        }

        $associationValue = $this->associationProperty->getValue($eventMessage->getPayload());        
        return (null === $associationValue) ? null : new AssociationValue($this->associationKey,
            $associationValue);
    }

    public function compareTo($other)
    {
        
    }

}
