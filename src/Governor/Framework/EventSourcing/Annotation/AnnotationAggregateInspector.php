<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\EventSourcing\Annotation;

use Doctrine\Common\Annotations\AnnotationReader;
use Governor\Framework\Domain\DomainEventMessageInterface;
use Governor\Framework\Common\ReflectionUtils;
use Governor\Framework\EventSourcing\IncompatibleAggregateException;

/**
 * Description of AnnotationAggregateInspector
 *
 * @author david
 */
class AnnotationAggregateInspector
{

    const AGGREGATE_IDENTIFIER_ANNOTATION = 'Governor\Framework\Annotations\AggregateIdentifier';
    const EVENT_HANDLER_ANNOTATION = 'Governor\Framework\Annotations\EventHandler';
    const EVENT_SOURCED_MEMBER_ANNOTATION = 'Governor\Framework\Annotations\EventSourcedMember';

    private $targetObject;
    private $reflectionClass;
    private $reader;

    public function __construct($targetObject)
    {
        $this->targetObject = $targetObject;
        $this->reader = new AnnotationReader();
        $this->reflectionClass = ReflectionUtils::getClass($this->targetObject);
    }

    public function getIdentifier()
    {
        foreach (ReflectionUtils::getProperties($this->reflectionClass) as $property) {
            $annot = $this->reader->getPropertyAnnotation($property,
                self::AGGREGATE_IDENTIFIER_ANNOTATION);

            if (null !== $annot) {
                $property->setAccessible(true);
                return $property->getValue($this->targetObject);
            }
        }

        throw new IncompatibleAggregateException(sprintf("The aggregate class [%s] does not specify an Identifier. " .
            "Ensure that the field containing the aggregate " .
            "identifier is annotated with @AggregateIdentifier.",
            $this->reflectionClass->getName()));
    }

    public function getChildEntities()
    {
        $entities = array();

        foreach (ReflectionUtils::getProperties($this->reflectionClass) as $property) {
            $annot = $this->reader->getPropertyAnnotation($property,
                self::EVENT_SOURCED_MEMBER_ANNOTATION);

            if (null !== $annot) {
                $property->setAccessible(true);
                $child = $property->getValue($this->targetObject);


                if (is_array($child)) {                    
                    $entities = array_merge($entities, $child);
                } else if ($child instanceof \IteratorAggregate) {
                    foreach ($child as $element) {
                        $entities[] = $element;
                    }
                } else {
                    $entities[] = $child;
                }
            }
        }

        return $entities;
    }

    public function findAndinvokeEventHandlers(DomainEventMessageInterface $event)
    {
        foreach (ReflectionUtils::getMethods($this->reflectionClass) as $method) {
            $annot = $this->reader->getMethodAnnotation($method,
                self::EVENT_HANDLER_ANNOTATION);

            if (null !== $annot) {
                $parameter = current($method->getParameters());

                if (null !== $parameter->getClass() && $parameter->getClass()->name === $event->getPayloadType()) {
                    $method->invokeArgs($this->targetObject,
                        array($event->getPayload()));
                }
            }
        }
    }

}
