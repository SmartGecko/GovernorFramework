<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Tests\EventSourcing;

use Ramsey\Uuid\Uuid;
use Governor\Framework\EventSourcing\AbstractEventSourcedAggregateRoot;
use Governor\Framework\Domain\DomainEventMessageInterface;
use Governor\Framework\Domain\GenericDomainEventMessage;
use Governor\Framework\Domain\MetaData;
use Governor\Tests\Stubs\StubDomainEvent;
use Governor\Tests\Stubs\StubAggregate;
use Governor\Framework\EventSourcing\AbstractEventSourcedEntity;

/**
 * Description of AbstractEventSourcedEntityTest
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a> 
 */
class AbstractEventSourcedEntityTest extends \PHPUnit_Framework_TestCase
{

    private $testSubject;

    public function setUp()
    {
        $this->testSubject = new StubEntity();
    }

    public function testRecursivelyApplyEvent()
    {
        $aggregateRoot = $this->getMockForAbstractClass(AbstractEventSourcedAggregateRoot::class);
        $this->testSubject->registerAggregateRoot($aggregateRoot);

        $this->testSubject->handleRecursively($this->domainEvent(new StubDomainEvent()));
        $this->assertEquals(1, $this->testSubject->invocationCount);
        $this->testSubject->handleRecursively($this->domainEvent(new StubDomainEvent()));
        $this->assertEquals(2, $this->testSubject->invocationCount);
        $this->assertEquals(1, $this->testSubject->child->invocationCount);
    }

    private function domainEvent(StubDomainEvent $stubDomainEvent)
    {
        return new GenericDomainEventMessage(Uuid::uuid1(), 0, $stubDomainEvent,
                MetaData::emptyInstance());
    }

    public function testApplyDelegatesToAggregateRoot()
    {
        $aggregateRoot = $this->getMockBuilder(AbstractEventSourcedAggregateRoot::class)
                ->disableOriginalConstructor()
                ->setMethods(array('apply'))
                ->getMockForAbstractClass();

        $this->testSubject->registerAggregateRoot($aggregateRoot);
        $event = new StubDomainEvent();

        $aggregateRoot->expects($this->once())
                ->method('apply');

        $this->testSubject->apply($event);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testDuplicateAggregateRootRegistration_DifferentAggregate()
    {
        $aggregateRoot1 = $this->getMockForAbstractClass(AbstractEventSourcedAggregateRoot::class);
        $aggregateRoot2 = $this->getMockForAbstractClass(AbstractEventSourcedAggregateRoot::class);

        $this->testSubject->registerAggregateRoot($aggregateRoot1);
        $this->testSubject->registerAggregateRoot($aggregateRoot2);
    }

    public function testDuplicateAggregateRootRegistration_SameAggregate()
    {
        $aggregateRoot = $this->getMockForAbstractClass(AbstractEventSourcedAggregateRoot::class);
        $this->testSubject->registerAggregateRoot($aggregateRoot);
        $this->testSubject->registerAggregateRoot($aggregateRoot);
    }

}

class StubEntity extends AbstractEventSourcedEntity
{

    public $invocationCount = 0;
    public $child;

    protected function getChildEntities()
    {
        return array($this->child);
    }

    protected function handle(DomainEventMessageInterface $event)
    {
        if (1 === $this->invocationCount && null === $this->child) {
            $this->child = new StubEntity();
        }
        $this->invocationCount++;
    }

}
