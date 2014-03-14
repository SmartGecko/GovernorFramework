<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\CommandHandling;

use Governor\Framework\Stubs\StubAggregate;
use Governor\Framework\UnitOfWork\DefaultUnitOfWork;
use Governor\Framework\UnitOfWork\CurrentUnitOfWork;
use Governor\Framework\Repository\NullLockManager;
use Governor\Framework\EventSourcing\EventSourcingRepository;
use Governor\Framework\EventSourcing\GenericAggregateFactory;
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
    private $mockLogger;

    public function setUp()
    {
        $this->mockEventStore = new StubEventStore();
        $this->mockEventBus = $this->getMock('Governor\Framework\EventHandling\EventBusInterface');
        $this->repository = new EventSourcingRepository('Governor\Framework\Stubs\StubAggregate',
            $this->mockEventBus, new NullLockManager(), $this->mockEventStore,
            new GenericAggregateFactory('Governor\Framework\Stubs\StubAggregate'));
        $this->aggregateIdentifier = "testAggregateIdentifier";
        $this->mockLogger = $this->getMock('Psr\Log\LoggerInterface');
    }

    public function testCommandHandlerLoadsSameAggregateTwice()
    {
        DefaultUnitOfWork::startAndGet($this->mockLogger);

        $stubAggregate = new StubAggregate($this->aggregateIdentifier);
        $stubAggregate->doSomething();
        $this->repository->add($stubAggregate);
        CurrentUnitOfWork::commit();

        DefaultUnitOfWork::startAndGet($this->mockLogger);
        $this->repository->load($this->aggregateIdentifier)->doSomething();
        $this->repository->load($this->aggregateIdentifier)->doSomething();
        CurrentUnitOfWork::commit();

        $es = $this->mockEventStore->readEvents("", $this->aggregateIdentifier);
        $this->assertTrue($es->hasNext());
        $this->assertEquals(0, $es->next()->getScn());
        $this->assertTrue($es->hasNext());
        $this->assertEquals(1, $es->next()->getScn());
        $this->assertTrue($es->hasNext());
        $this->assertEquals(2, $es->next()->getScn());
        $this->assertFalse($es->hasNext());
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
