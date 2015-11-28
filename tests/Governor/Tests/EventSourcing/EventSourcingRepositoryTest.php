<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Tests\EventSourcing;

use Ramsey\Uuid\Uuid;
use Governor\Framework\Domain\DomainEventMessageInterface;
use Governor\Framework\Domain\GenericDomainEventMessage;
use Governor\Framework\Domain\SimpleDomainEventStream;
use Governor\Framework\Domain\MetaData;
use Governor\Framework\Repository\NullLockManager;
use Governor\Framework\UnitOfWork\DefaultUnitOfWork;
use Governor\Framework\UnitOfWork\CurrentUnitOfWork;
use Governor\Tests\Stubs\StubDomainEvent;
use Governor\Framework\Repository\ConflictingAggregateVersionException;
use Governor\Framework\EventSourcing\AbstractAggregateFactory;
use Governor\Framework\EventSourcing\AbstractEventSourcedAggregateRoot;
use Governor\Framework\EventSourcing\EventSourcingRepository;
use Governor\Framework\EventStore\SnapshotEventStoreInterface;
use Governor\Framework\EventHandling\EventBusInterface;

/**
 * EventSourcingRepository unit tests
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
class EventSourcingRepositoryTest extends \PHPUnit_Framework_TestCase
{

    private $mockEventStore;
    private $mockEventBus;
    private $testSubject;
    private $unitOfWork;
    private $stubAggregateFactory;

    public function setUp()
    {
        $this->mockEventStore = $this->getMock(SnapshotEventStoreInterface::class);
        $this->mockEventBus = $this->getMock(EventBusInterface::class);//,
            //array('publish', 'subscribe', 'unsubscribe'));
        $this->stubAggregateFactory = new StubAggregateFactory();
        $this->testSubject = new EventSourcingRepository(TestAggregate::class,
            $this->mockEventBus, new NullLockManager(), $this->mockEventStore,
            $this->stubAggregateFactory);

        $this->unitOfWork = DefaultUnitOfWork::startAndGet();
    }

    public function tearDown()
    {
        if ($this->unitOfWork->isStarted()) {
            $this->unitOfWork->rollback();
        }
    }

    public function testLoadAndSaveAggregate()
    {
        $identifier = Uuid::uuid1()->toString();
        $event1 = new GenericDomainEventMessage($identifier, 1, new \stdClass(),
            MetaData::emptyInstance());
        $event2 = new GenericDomainEventMessage($identifier, 2, new \stdClass(),
            MetaData::emptyInstance());

        $this->mockEventStore->expects($this->any())
            ->method('readEvents')
            ->with($this->identicalTo("test"), $this->identicalTo($identifier))
            ->will($this->returnValue(new SimpleDomainEventStream(array(
                    $event1, $event2))));

        $aggregate = $this->testSubject->load($identifier, null);

        $this->assertEquals(0, $aggregate->getUncommittedEventCount());
        $this->assertEquals(2, count($aggregate->getHandledEvents()));
        $this->assertSame($event1, $aggregate->getHandledEvents()[0]);
        $this->assertSame($event2, $aggregate->getHandledEvents()[1]);

        // now the aggregate is loaded (and hopefully correctly locked)
        $event3 = new StubDomainEvent();

        $this->mockEventStore->expects($this->once())
            ->method('appendEvents');

        $this->mockEventBus->expects($this->once())
            ->method('publish')
            ->with($this->anything());

        $aggregate->apply($event3);

        CurrentUnitOfWork::commit();

        $this->assertEquals(0, $aggregate->getUncommittedEventCount());
    }

    public function testLoad_FirstEventIsSnapshot()
    {
        $identifier = Uuid::uuid1()->toString();
        $aggregate = new TestAggregate($identifier);

        $this->mockEventStore->expects($this->once())
            ->method('readEvents')
            ->with($this->identicalTo("test"), $this->identicalTo($identifier))
            ->will($this->returnValue(new SimpleDomainEventStream(array(new GenericDomainEventMessage($identifier,
                        10, $aggregate)))));

        $this->assertSame($aggregate, $this->testSubject->load($identifier));
    }

    public function testLoadAndSaveWithConflictingChanges()
    {
        $conflictResolver = $this->getMock('Governor\Framework\EventSourcing\ConflictResolverInterface');
        $identifier = Uuid::uuid1()->toString();
        $event2 = new GenericDomainEventMessage($identifier, 2, new \stdClass(),
            MetaData::emptyInstance());
        $event3 = new GenericDomainEventMessage($identifier, 3, new \stdClass(),
            MetaData::emptyInstance());

        $this->mockEventStore->expects($this->once())
            ->method('readEvents')
            ->with($this->identicalTo("test"), $this->identicalTo($identifier))
            ->will($this->returnValue(new SimpleDomainEventStream(array(new GenericDomainEventMessage($identifier,
                        1, new \stdClass()), $event2, $event3))));

        $this->testSubject->setConflictResolver($conflictResolver);

        $actual = $this->testSubject->load($identifier, 1);

        $appliedEvent = new StubDomainEvent();

     //   $conflictResolver->expects($this->never())
          //  ->method('resolveConflicts');
        //!!! TODO conflic resolving listener

        $actual->apply($appliedEvent);

        $conflictResolver->expects($this->once())
            ->method('resolveConflicts')
            ->with($this->anything(), $this->anything());


        CurrentUnitOfWork::commit();

        /*

          verify(conflictResolver, never()).resolveConflicts(anyListOf(DomainEventMessage.class), anyListOf(
          DomainEventMessage.class));
          final StubDomainEvent appliedEvent = new StubDomainEvent();
          actual.apply(appliedEvent);

          CurrentUnitOfWork.commit();

          verify(conflictResolver).resolveConflicts(payloadsEqual(appliedEvent), eq(Arrays.asList(event2, event3))); */
    }

    /*
      private List<DomainEventMessage> payloadsEqual(final StubDomainEvent expectedEvent) {
      return argThat(new BaseMatcher<List<DomainEventMessage>>() {
      @Override
      public boolean matches(Object o) {
      return o instanceof List && ((List) o).size() >= 0
      && ((Message) ((List) o).get(0)).getPayload().equals(expectedEvent);
      }

      @Override
      public void describeTo(Description description) {
      description.appendText("List with an event with a")
      .appendText(expectedEvent.getClass().getName())
      .appendText(" payload");
      }
      });
      } */

    public function testLoadWithConflictingChanges_NoConflictResolverSet()
    {
        $identifier = Uuid::uuid1()->toString();
        $event2 = new GenericDomainEventMessage($identifier, 2, new \stdClass(),
            MetaData::emptyInstance());
        $event3 = new GenericDomainEventMessage($identifier, 3, new \stdClass(),
            MetaData::emptyInstance());

        $this->mockEventStore->expects($this->once())
            ->method('readEvents')
            ->with($this->identicalTo("test"), $this->identicalTo($identifier))
            ->will($this->returnValue(new SimpleDomainEventStream(array(new GenericDomainEventMessage($identifier,
                        1, new \stdClass()), $event2, $event3))));

        try {
            $this->testSubject->load($identifier, 1);
            $this->fail("Expected ConflictingAggregateVersionException");
        } catch (ConflictingAggregateVersionException $ex) {
            $this->assertEquals($identifier, $ex->getAggregateIdentifier());
            $this->assertEquals(1, $ex->getExpectedVersion());
            $this->assertEquals(3, $ex->getActualVersion());
        }
    }

    public function testLoadWithConflictingChanges_NoConflictResolverSet_UsingTooHighExpectedVersion()
    {
        $identifier = Uuid::uuid1()->toString();
        $event2 = new GenericDomainEventMessage($identifier, 2, new \stdClass(),
            MetaData::emptyInstance());
        $event3 = new GenericDomainEventMessage($identifier, 3, new \stdClass(),
            MetaData::emptyInstance());

        $this->mockEventStore->expects($this->once())
            ->method('readEvents')
            ->with($this->identicalTo("test"), $this->identicalTo($identifier))
            ->will($this->returnValue(new SimpleDomainEventStream(array(new GenericDomainEventMessage($identifier,
                        1, new \stdClass()), $event2, $event3))));
        try {
            $this->testSubject->load($identifier, 100);
            $this->fail("Expected ConflictingAggregateVersionException");
        } catch (ConflictingAggregateVersionException $ex) {
            $this->assertEquals($identifier, $ex->getAggregateIdentifier());
            $this->assertEquals(100, $ex->getExpectedVersion());
            $this->assertEquals(3, $ex->getActualVersion());
        }
    }

    public function testLoadAndSaveWithoutConflictingChanges()
    {
        $conflictResolver = $this->getMock('Governor\Framework\EventSourcing\ConflictResolverInterface');
        $identifier = Uuid::uuid1()->toString();
        $event2 = new GenericDomainEventMessage($identifier, 2, new \stdClass(),
            MetaData::emptyInstance());
        $event3 = new GenericDomainEventMessage($identifier, 3, new \stdClass(),
            MetaData::emptyInstance());

        $this->mockEventStore->expects($this->once())
            ->method('readEvents')
            ->with($this->identicalTo("test"), $this->identicalTo($identifier))
            ->will($this->returnValue(new SimpleDomainEventStream(array(new GenericDomainEventMessage($identifier,
                        1, new \stdClass()), $event2, $event3))));

        $this->testSubject->setConflictResolver($conflictResolver);

        $actual = $this->testSubject->load($identifier, 3);

        $conflictResolver->expects($this->never())
            ->method('resolveConflicts');

        $actual->apply(new StubDomainEvent());

        $conflictResolver->expects($this->never())
            ->method('resolveConflicts');

        CurrentUnitOfWork::commit();
    }

    /*
      @Test
      public void testLoadEventsWithDecorators() {
      UUID identifier = UUID.randomUUID();
      SpyEventPreprocessor decorator1 = new SpyEventPreprocessor();
      SpyEventPreprocessor decorator2 = new SpyEventPreprocessor();
      testSubject.setEventStreamDecorators(Arrays.asList(decorator1, decorator2));
      when(mockEventStore.readEvents("test", identifier)).thenReturn(
      new SimpleDomainEventStream(new GenericDomainEventMessage<String>(identifier, (long) 1,
      "Mock contents",
      MetaData.emptyInstance()
      ),
      new GenericDomainEventMessage<String>(identifier, (long) 2,
      "Mock contents",
      MetaData.emptyInstance()
      ),
      new GenericDomainEventMessage<String>(identifier, (long) 3,
      "Mock contents",
      MetaData.emptyInstance()
      )));
      TestAggregate aggregate = testSubject.load(identifier);
      // loading them in...
      InOrder inOrder = Mockito.inOrder(decorator1.lastSpy, decorator2.lastSpy);
      inOrder.verify(decorator2.lastSpy).next();
      inOrder.verify(decorator1.lastSpy).next();

      inOrder.verify(decorator2.lastSpy).next();
      inOrder.verify(decorator1.lastSpy).next();

      inOrder.verify(decorator2.lastSpy).next();
      inOrder.verify(decorator1.lastSpy).next();
      aggregate.apply(new StubDomainEvent());
      aggregate.apply(new StubDomainEvent());
      }

      @Test
      public void testSaveEventsWithDecorators() {
      testSubject = new EventSourcingRepository<TestAggregate>(subtAggregateFactory, new EventStore() {
      @Override
      public void appendEvents(String type, DomainEventStream events) {
      while (events.hasNext()) {
      events.next();
      }
      }

      @Override
      public DomainEventStream readEvents(String type, Object identifier) {
      return mockEventStore.readEvents(type, identifier);
      }
      });
      testSubject.setEventBus(mockEventBus);
      SpyEventPreprocessor decorator1 = new SpyEventPreprocessor();
      SpyEventPreprocessor decorator2 = new SpyEventPreprocessor();
      testSubject.setEventStreamDecorators(Arrays.asList(decorator1, decorator2));
      UUID identifier = UUID.randomUUID();
      when(mockEventStore.readEvents("test", identifier)).thenReturn(
      new SimpleDomainEventStream(new GenericDomainEventMessage<String>(identifier, (long) 3,
      "Mock contents",
      MetaData.emptyInstance()
      )));
      TestAggregate aggregate = testSubject.load(identifier);
      aggregate.apply(new StubDomainEvent());
      aggregate.apply(new StubDomainEvent());

      CurrentUnitOfWork.commit();

      InOrder inOrder = Mockito.inOrder(decorator1.lastSpy, decorator2.lastSpy);
      inOrder.verify(decorator1.lastSpy).next();
      inOrder.verify(decorator2.lastSpy).next();

      inOrder.verify(decorator1.lastSpy).next();
      inOrder.verify(decorator2.lastSpy).next();
      }



      public static class SpyEventPreprocessor implements EventStreamDecorator {

      private DomainEventStream lastSpy;

      @Override
      public DomainEventStream decorateForRead(final String aggregateType, Object aggregateIdentifier,
      final DomainEventStream eventStream) {
      createSpy(eventStream);
      return lastSpy;
      }

      @Override
      public DomainEventStream decorateForAppend(final String aggregateType, EventSourcedAggregateRoot aggregate,
      DomainEventStream eventStream) {
      createSpy(eventStream);
      return lastSpy;
      }

      private void createSpy(final DomainEventStream eventStream) {
      lastSpy = mock(DomainEventStream.class);
      when(lastSpy.next()).thenAnswer(new Answer<Object>() {
      @Override
      public Object answer(InvocationOnMock invocation) throws Throwable {
      return eventStream.next();
      }
      });
      when(lastSpy.hasNext()).thenAnswer(new Answer<Object>() {
      @Override
      public Object answer(InvocationOnMock invocation) throws Throwable {
      return eventStream.hasNext();
      }
      });
      when(lastSpy.peek()).thenAnswer(new Answer<Object>() {
      @Override
      public Object answer(InvocationOnMock invocation) throws Throwable {
      return eventStream.peek();
      }
      });
      }
      } */
}

class StubAggregateFactory extends AbstractAggregateFactory
{

    public function doCreateAggregate($aggregateIdentifier,
        DomainEventMessageInterface $firstEvent)
    {
        return new TestAggregate($aggregateIdentifier);
    }

    public function getTypeIdentifier()
    {
        return "test";
    }

    public function getAggregateType()
    {
        return 'Governor\Framework\EventSourcing\TestAggregate';
    }

}

class TestAggregate extends AbstractEventSourcedAggregateRoot
{

    private $handledEvents = array();
    private $identifier;

    public function __construct($identifier)
    {
        $this->identifier = $identifier;
    }

    public function apply($payload, MetaData $metaData = null)
    {
        parent::apply($payload, $metaData);
    }

    protected function getChildEntities()
    {
        return null;
    }

    protected function handle(DomainEventMessageInterface $event)
    {
        $this->identifier = $event->getAggregateIdentifier();
        $this->handledEvents[] = $event;
    }

    public function getHandledEvents()
    {
        return $this->handledEvents;
    }

    public function getIdentifier()
    {
        return $this->identifier;
    }

}
