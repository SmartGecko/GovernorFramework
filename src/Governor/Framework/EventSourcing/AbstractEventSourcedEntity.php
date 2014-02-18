<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\EventSourcing;

use Governor\Framework\Domain\MetaData;
use Governor\Framework\Domain\DomainEventMessageInterface;

/**
 * Description of AbstractEventSourcedEntity
 *
 * @author david
 */
abstract class AbstractEventSourcedEntity implements EventSourcedEntityInterface
{

    private $aggregateRoot;

    public function handleRecursively(DomainEventMessageInterface $event)
    {
        $this->handle($event);

        if (null === $childEntities = $this->getChildEntities()) {
            return;
        }

        foreach ($childEntities as $child) {
            if (null !== $child) {
                $child->registerAggregateRoot($this->aggregateRoot);
                $child->handleRecursively($event);
            }
        }
    }

    public function registerAggregateRoot(AbstractEventSourcedAggregateRoot $aggregateRootToRegister)
    {
        if (null !== $this->aggregateRoot && $this->aggregateRoot !== $aggregateRootToRegister) {
            throw new \RuntimeException("Cannot register new aggregate. "
            . "This entity is already part of another aggregate");
        }
        
        $this->aggregateRoot = $aggregateRootToRegister;
    }

    protected abstract function getChildEntities();

    protected function handle(DomainEventMessageInterface $event)
    {       
    }

    public function apply($event, MetaData $metaData = null)
    {
        if (null === $this->aggregateRoot) {
            throw new \RuntimeException("The aggregate root is unknown. "
            . "Is this entity properly registered as the child of an aggregate member?");
        }

        $this->aggregateRoot->apply($event, $metaData);
    }

    protected function getAggregateRoot()
    {
        return $this->aggregateRoot;
    }

}
