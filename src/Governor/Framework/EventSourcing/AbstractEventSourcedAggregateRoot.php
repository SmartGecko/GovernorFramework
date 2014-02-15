<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\EventSourcing;

use Doctrine\Common\Annotations\AnnotationReader;
use Governor\Framework\Domain\MetaData;
use Governor\Framework\Domain\GenericDomainEventMessage;
use Governor\Framework\Domain\DomainEventMessageInterface;
use Governor\Framework\Domain\DomainEventStreamInterface;
use Governor\Framework\Domain\AbstractAggregateRoot;

/**
 * Description of AbstractEventSourcedAggregateRoot
 *
 * @author david
 */
abstract class AbstractEventSourcedAggregateRoot extends AbstractAggregateRoot implements EventSourcedAggregateRootInterface
{

    public function initializeState(DomainEventStreamInterface $domainEventStream)
    {
        if (0 !== $this->getUncommittedEventCount()) {
            throw new \RuntimeException("Aggregate is already initialized");
        }

        $lastScn = -1;

        while ($domainEventStream->hasNext()) {
            $event = $domainEventStream->next();
            $lastScn = $event->getScn();                   
            $this->handleRecursively($event);
        }

        $this->initializeEventStream($lastScn);
    }

    protected abstract function getChildEntities();

    protected function handle(DomainEventMessageInterface $event)
    {
        $reflectionClass = new \ReflectionClass($this);
        $reader = new AnnotationReader();

        foreach ($reflectionClass->getMethods() as $method) {
            $annot = $reader->getMethodAnnotation($method,
                'Governor\Framework\Annotations\EventHandler');

            if (null !== $annot) {
                $parameter = current($method->getParameters());

                if (null !== $parameter->getClass() && $parameter->getClass()->name === $event->getPayloadType()) {
                    $method->invokeArgs($this, array($event->getPayload()));
                }
            }
        }
    }

    protected function apply($payload, MetaData $metaData = null)
    {
        $metaData = isset($metaData) ? $metaData : MetaData::emptyInstance();

        if (null === $this->getIdentifier()) {
            if ($this->getUncommittedEventCount() > 0 || $this->getVersion() !== null) {
                throw new \RuntimeException("The Aggregate Identifier has not been initialized. "
                . "It must be initialized at the latest when the "
                . "first event is applied.");
            }
            $this->handleRecursively(new GenericDomainEventMessage(null, 0,
                $payload, $metaData));
            $this->registerEvent($payload, $metaData);
        } else {
            $event = $this->registerEvent($payload, $metaData);
            $this->handleRecursively($event);
        }
    }

    private function handleRecursively(DomainEventMessageInterface $event)
    {
        $this->handle($event);

        if (null === $childEntities = $this->getChildEntities()) {
            return;
        }

        foreach ($childEntities as $child) {
            if (null !== $child) {
                $child->registerAggregateRoot($this);
                $child->handleRecursively($event);
            }
        }
    }

    public function getVersion()
    {
        return $this->getLastCommittedEventScn();
    }

}
