<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Tests\Stubs;

use Rhumsaa\Uuid\Uuid;
use Governor\Framework\EventSourcing\AbstractEventSourcedAggregateRoot;
use Governor\Framework\Domain\MetaData;
use Governor\Framework\Domain\DomainEventMessageInterface;
use Governor\Framework\Domain\GenericDomainEventMessage;

class StubAggregate extends AbstractEventSourcedAggregateRoot
{

    private $invocationCount;
    private $identifier;

    public function __construct($id = null)
    {
        $this->identifier = (null === $id) ? Uuid::uuid1()->toString() : $id;        
    }

    public function doSomething()
    {
        $this->apply(new StubDomainEvent());
    }

    public function getIdentifier()
    {
        return $this->identifier;
    }

    protected function handle(DomainEventMessageInterface $event)
    {        
        $this->identifier = $event->getAggregateIdentifier();
        $this->invocationCount++;
    }

    public function getInvocationCount()
    {
        return $this->invocationCount;
    }

    public function createSnapshotEvent()
    {        
        return new GenericDomainEventMessage($this->getIdentifier(), 5,
            new StubDomainEvent(), MetaData::emptyInstance());
    }

    public function delete()
    {
        $this->apply(new StubDomainEvent());
        $this->markDeleted();
    }

    protected function getChildEntities()
    {
        return null;
    }

}
