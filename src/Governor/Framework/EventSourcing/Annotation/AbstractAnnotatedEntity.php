<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\EventSourcing\Annotation;

use Governor\Framework\EventSourcing\AbstractEventSourcedEntity;
use Governor\Framework\Domain\DomainEventMessageInterface;
/**
 * Description of AbstractAnnotatedEntity
 *
 * @author david
 */
class AbstractAnnotatedEntity extends AbstractEventSourcedEntity
{
    private $inspector;
    
    protected function getChildEntities()
    {
        $this->ensureInspectorStarted();
        return $this->inspector->getChildEntities();
    }

    protected function handle(DomainEventMessageInterface $event)
    {
        $this->ensureInspectorStarted();
        $this->inspector->findAndinvokeEventHandlers($event);
    }
    
    private function ensureInspectorStarted()
    {
        if (null === $this->inspector) {
            $this->inspector = new AnnotationAggregateInspector($this);
        }                
    }

}
