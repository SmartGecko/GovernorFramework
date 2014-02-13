<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\CommandHandling;

use Governor\Framework\UnitOfWork\DefaultUnitOfWork;
use Governor\Framework\EventStore\EventStoreInterface;

/**
 * Description of CommandHandlingTest
 *
 * @author david
 */
class CommandHandlingTest extends \PHPUnit_Framework_TestCase
{

    private $repository;
    private $aggregateIdentifier;
    private $mockEventBus;
    private $mockEventStore;

    public function setUp()
    {
        $this->mockEventStore = new StubEventStore();
        //$this->repository = new EventSourcingRepository<StubAggregate>(StubAggregate.class, mockEventStore);
        //$this->mockEventBus = mock(EventBus.class);
        //repository.setEventBus(mockEventBus);
        $this->aggregateIdentifier = "testAggregateIdentifier";
    }

    public function testCommandHandlerLoadsSameAggregateTwice()
    {
       // DefaultUnitOfWork::startAndGet();
        /*
          StubAggregate stubAggregate = new StubAggregate(aggregateIdentifier);
          stubAggregate.doSomething();
          repository.add(stubAggregate);
          CurrentUnitOfWork.commit();

          DefaultUnitOfWork.startAndGet();
          repository.load(aggregateIdentifier).doSomething();
          repository.load(aggregateIdentifier).doSomething();
          CurrentUnitOfWork.commit();

          DomainEventStream es = mockEventStore.readEvents("", aggregateIdentifier);
          assertTrue(es.hasNext());
          assertEquals((Object) 0L, es.next().getSequenceNumber());
          assertTrue(es.hasNext());
          assertEquals((Object) 1L, es.next().getSequenceNumber());
          assertTrue(es.hasNext());
          assertEquals((Object) 2L, es.next().getSequenceNumber());
          assertFalse(es.hasNext()); */
    }

}

class StubEventStore implements EventStoreInterface
{

    private $storedEvents = array();

    public function appendEvents($type,
            \Governor\Framework\Domain\DomainEventStreamInterface $events)
    {
        while ($events->hasNext()) {
            $this->storedEvents[] = $events->next();
        }
    }

    public function readEvents($type, $identifier)
    {
        return new \Governor\Framework\Domain\SimpleDomainEventStream($this->storedEvents);
    }

}
