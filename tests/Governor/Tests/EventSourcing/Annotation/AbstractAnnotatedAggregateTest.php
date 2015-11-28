<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Tests\EventSourcing\Annotation;

use Ramsey\Uuid\Uuid;
use Governor\Tests\Stubs\StubDomainEvent;
use Governor\Framework\Annotations\AggregateIdentifier;
use Governor\Framework\Annotations\EventSourcedMember;
use Governor\Framework\Annotations\EventHandler;
use Governor\Framework\EventSourcing\Annotation\AbstractAnnotatedAggregateRoot;
use Governor\Framework\EventSourcing\Annotation\AbstractAnnotatedEntity;

/**
 * Description of AbstractAnnotatedAggregateTest
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a> 
 * @covers \Governor\Framework\Annotations\AggregateIdentifier
 * @covers \Governor\Framework\Annotations\EventSourcedMember
 * @covers \Governor\Framework\Annotations\EventHandler
 */
class AbstractAnnotatedAggregateTest extends \PHPUnit_Framework_TestCase
{

    private $testSubject;

    public function testApplyEvent()
    {
        $this->testSubject = new SimpleAggregateRoot();

        $this->assertNotNull($this->testSubject->getIdentifier());
        $this->assertEquals(1, $this->testSubject->getUncommittedEventCount());
        // this proves that a newly added entity is also notified of an event
        $this->assertEquals(1, $this->testSubject->getEntity()->invocationCount);

        $this->testSubject->doSomething();

        $this->assertEquals(2, $this->testSubject->invocationCount);
        $this->assertEquals(2, $this->testSubject->getEntity()->invocationCount);
    }

    public function testIdentifierInitialization_LateInitialization()
    {
        $aggregate = new LateIdentifiedAggregate();
        $this->assertEquals("lateIdentifier", $aggregate->getIdentifier());
        $this->assertEquals("lateIdentifier",
            $aggregate->getUncommittedEvents()->peek()->getAggregateIdentifier());
    }

}

class LateIdentifiedAggregate extends AbstractAnnotatedAggregateRoot
{

    /**
     * @AggregateIdentifier
     */
    public $aggregateIdentifier;

    public function __construct()
    {
        $this->apply(new StubDomainEvent());
    }

    /**
     *  @EventHandler
     */
    public function myEventHandlerMethod(StubDomainEvent $event)
    {
        $this->aggregateIdentifier = "lateIdentifier";
    }

}

class SimpleAggregateRoot extends AbstractAnnotatedAggregateRoot
{

    public $invocationCount;

    /**
     * @EventSourcedMember
     */
    public $entity;

    /**
     * @AggregateIdentifier
     */
    public $identifier;

    public function __construct()
    {
        $this->identifier = Uuid::uuid1();
        $this->apply(new StubDomainEvent());
    }

    /**
     * @EventHandler
     */
    public function myEventHandlerMethod(StubDomainEvent $event)
    {
        $this->invocationCount++;
        if (null === $this->entity) {
            $this->entity = new SimpleEntity();
        }
    }

    public function getEntity()
    {
        return $this->entity;
    }

    public function doSomething()
    {
        $this->apply(new StubDomainEvent());
    }

}

class SimpleEntity extends AbstractAnnotatedEntity
{

    public $invocationCount;

    /**
     * @EventHandler
     */
    public function myEventHandlerMethod(StubDomainEvent $event)
    {
        $this->invocationCount++;
    }

}
