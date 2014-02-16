<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Repository;

use Rhumsaa\Uuid\Uuid;
use Governor\Framework\Repository\AggregateNotFoundException;
use Governor\Framework\Repository\ConflictingAggregateVersionException;
use Governor\Framework\Domain\AbstractAggregateRoot;
use Governor\Framework\UnitOfWork\DefaultUnitOfWork;
use Governor\Framework\UnitOfWork\CurrentUnitOfWork;

/**
 * Description of GenericDoctrineRepositoryTest
 *
 * @author david
 */
class GenericDoctrineRepositoryTest extends \PHPUnit_Framework_TestCase
{

    private $mockEntityManager;
    private $mockEventBus;
    private $testSubject; // GenericDoctrineRepository
    private $aggregateId;
    private $aggregate; // StubDoctrineAggregate

    public function setUp()
    {
        $this->mockEntityManager = $this->getMock('Doctrine\ORM\EntityManager',
            array('find', 'flush', 'persist', 'remove'), array(), '', false);
        $this->mockEventBus = $this->getMock('Governor\Framework\EventHandling\EventBusInterface');
        $this->testSubject = new GenericDoctrineRepository('Governor\Framework\Repository\StubDoctrineAggregate',
             $this->mockEventBus, new NullLockManager(), $this->mockEntityManager);

        $this->aggregateId = "123";
        $this->aggregate = new StubDoctrineAggregate($this->aggregateId);
        DefaultUnitOfWork::startAndGet();
    }

    public function tearDown()
    {
        while (CurrentUnitOfWork::isStarted()) {
            CurrentUnitOfWork::get()->rollback();
        }
    }

    public function testLoadAggregate()
    {
        $this->mockEntityManager->expects($this->once())
            ->method('find')
            ->with($this->equalTo('Governor\Framework\Repository\StubDoctrineAggregate'),
                $this->equalTo('123'))
            ->will($this->returnValue($this->aggregate));

        $actualResult = $this->testSubject->load($this->aggregateId);
        $this->assertSame($this->aggregate, $actualResult);
    }

    public function testLoadAggregate_NotFound()
    {
        $aggregateIdentifier = Uuid::uuid1()->toString();
        try {
            $this->testSubject->load($aggregateIdentifier);
            $this->fail("Expected AggregateNotFoundException");
        } catch (AggregateNotFoundException $ex) {
            $this->assertEquals($aggregateIdentifier, $ex->getAggregateId());
        }
    }

    public function testLoadAggregate_WrongVersion()
    {
        $this->mockEntityManager->expects($this->once())
            ->method('find')
            ->with($this->equalTo('Governor\Framework\Repository\StubDoctrineAggregate'),
                $this->equalTo('123'))
            ->will($this->returnValue($this->aggregate));

        try {
            $this->testSubject->load($this->aggregateId, 2);
            $this->fail("Expected ConflictingAggregateVersionException");
        } catch (ConflictingAggregateVersionException $ex) {
            $this->assertEquals(2, $ex->getExpectedVersion());
            $this->assertEquals(0, $ex->getActualVersion());
        }
    }

    public function testPersistAggregate_DefaultFlushMode()
    {
        $this->mockEntityManager->expects($this->once())
            ->method('flush');

        $reflection = new \ReflectionClass('Governor\Framework\Repository\GenericDoctrineRepository');
        $method = $reflection->getMethod('doSaveWithLock');
        $method->setAccessible(true);

        $method->invokeArgs($this->testSubject, array($this->aggregate));
        
        $method2 = $reflection->getMethod('postSave');
        $method2->setAccessible(true);

        $method2->invokeArgs($this->testSubject, array($this->aggregate));
    }

    public function testPersistAggregate_ExplicitFlushModeOn()
    {
        $this->mockEntityManager->expects($this->once())
            ->method('flush');

        $reflection = new \ReflectionClass('Governor\Framework\Repository\GenericDoctrineRepository');
        $method = $reflection->getMethod('doSaveWithLock');
        $method->setAccessible(true);

        $method->invokeArgs($this->testSubject, array($this->aggregate));
        
        $method2 = $reflection->getMethod('postSave');
        $method2->setAccessible(true);

        $method2->invokeArgs($this->testSubject, array($this->aggregate));
    }

    public function testPersistAggregate_ExplicitFlushModeOff()
    {
        $this->testSubject->setForceFlushOnSave(false);
        $this->mockEntityManager->expects($this->never())
            ->method('flush');

        $this->assertFalse($this->testSubject->isForceFlushOnSave());

        $reflection = new \ReflectionClass('Governor\Framework\Repository\GenericDoctrineRepository');
        $method = $reflection->getMethod('doSaveWithLock');
        $method->setAccessible(true);

        $method->invokeArgs($this->testSubject, array($this->aggregate));
    }

    public function testRemoveAggregate_ExplicitFlushModeOn()
    {
        $this->mockEntityManager->expects($this->once())
            ->method('flush');

        $reflection = new \ReflectionClass('Governor\Framework\Repository\GenericDoctrineRepository');
        $method = $reflection->getMethod('doDeleteWithLock');
        $method->setAccessible(true);

        $method->invokeArgs($this->testSubject, array($this->aggregate));
    }

    public function testRemoveAggregate_ExplicitFlushModeOff()
    {
        $this->testSubject->setForceFlushOnSave(false);
        $this->mockEntityManager->expects($this->never())
            ->method('flush');

        $this->assertFalse($this->testSubject->isForceFlushOnSave());

        $reflection = new \ReflectionClass('Governor\Framework\Repository\GenericDoctrineRepository');
        $method = $reflection->getMethod('doDeleteWithLock');
        $method->setAccessible(true);

        $method->invokeArgs($this->testSubject, array($this->aggregate));
    }

}

class StubDoctrineAggregate extends AbstractAggregateRoot
{

    private $identifier;

    function __construct($identifier)
    {
        $this->identifier = $identifier;
    }

    public function getIdentifier()
    {
        return $this->identifier;
    }

    public function getVersion()
    {
        return 0;
    }

}
