<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\EventSourcing;

use Rhumsaa\Uuid\Uuid;
use Governor\Framework\Domain\AbstractAggregateRoot;
use Governor\Framework\Domain\DomainEventMessageInterface;
use Governor\Framework\Domain\GenericDomainEventMessage;
use Governor\Framework\Stubs\StubAggregate;

/**
 * Description of GenericAggregateFactoryTest
 *
 * @author david
 */
class GenericAggregateFactoryTest extends \PHPUnit_Framework_TestCase
{

    public function testInitializeRepository_UnsitableAggregate()
    {

        try {
            $factory = new GenericAggregateFactory(get_class(new UnsuitableAggregate()));
            $this->fail("Expected InvalidArgumentException");
        } catch (\InvalidArgumentException $ex) {
            // we got it
        }
    }

    public function testAggregateTypeIsSimpleName()
    {
        $factory = new GenericAggregateFactory(get_class(new StubAggregate()));
        $this->assertEquals("StubAggregate", $factory->getTypeIdentifier());
    }

    public function testInitializeFromAggregateSnapshot()
    {
        $aggregate = new StubAggregate("stubId");
        $aggregate->doSomething();
        $aggregate->commitEvents();
        $snapshotMessage = new GenericDomainEventMessage($aggregate->getIdentifier(),
            $aggregate->getVersion(), $aggregate);

        $factory = new GenericAggregateFactory(get_class($aggregate));
        $this->assertEquals("StubAggregate", $factory->getTypeIdentifier());

        $this->assertSame($aggregate,
            $factory->createAggregate($aggregate->getIdentifier(),
                $snapshotMessage));
    }
}

class UnsuitableAggregate extends AbstractAggregateRoot
{

    protected function getChildEntities()
    {
        return null;
    }

    protected function handle(DomainEventMessageInterface $event)
    {
        
    }

    public function getIdentifier()
    {
        return "unsuitableAggregateId";
    }

}
