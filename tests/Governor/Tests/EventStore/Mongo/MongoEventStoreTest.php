<?php

/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * The software is based on the Axon Framework project which is
 * licensed under the Apache 2.0 license. For more information on the Axon Framework
 * see <http://www.axonframework.org/>.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.governor-framework.org/>.
 */

namespace Governor\Tests\EventStore\Mongo;

use Ramsey\Uuid\Uuid;
use Governor\Framework\EventStore\Mongo\DocumentPerEventStorageStrategy;
use Governor\Framework\Serializer\JMSSerializer;
use Psr\Log\LoggerInterface;
use Governor\Framework\EventStore\Mongo\MongoEventStore;
use Governor\Framework\Domain\GenericDomainEventMessage;
use Governor\Framework\EventStore\Mongo\DefaultMongoTemplate;
use Governor\Framework\EventStore\EventVisitorInterface;
use Governor\Framework\Domain\SimpleDomainEventStream;
use Governor\Framework\Repository\ConcurrencyException;
use Governor\Framework\EventSourcing\Annotation\AbstractAnnotatedAggregateRoot;
use Governor\Framework\Annotations as Governor;

class MongoEventStoreTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var MongoEventStore
     */
    private $testSubject;

    /**
     * @var DefaultMongoTemplate
     */
    private $mongoTemplate;

    /**
     * @var StubAggregateRoot
     */
    private $aggregate1;

    /**
     * @var StubAggregateRoot
     */
    private $aggregate2;


    public function setUp()
    {
        try {
            $this->mongoTemplate = new DefaultMongoTemplate('mongodb://localhost:27017', 'governortest');

            $this->testSubject = new MongoEventStore(
                $this->mongoTemplate,
                new JMSSerializer(),
                new DocumentPerEventStorageStrategy()
            );

            $this->mongoTemplate->domainEventCollection()->remove([]);
            $this->mongoTemplate->snapshotEventCollection()->remove([]);
        } catch (\Exception $ex) {
            $this->logger->error("No Mongo instance found. Ignoring test.");
        }
        $this->aggregate1 = new StubAggregateRoot();
        for ($t = 0; $t < 10; $t++) {
            $this->aggregate1->changeState();
        }

        $this->aggregate2 = new StubAggregateRoot();
        $this->aggregate2->changeState();
        $this->aggregate2->changeState();
        $this->aggregate2->changeState();

    }

    public function testStoreAndLoadEvents()
    {
        $this->assertNotNull($this->testSubject);

        $this->testSubject->appendEvents("test", $this->aggregate1->getUncommittedEvents());

        $this->assertEquals(
            $this->aggregate1->getUncommittedEventCount(),
            $this->mongoTemplate->domainEventCollection()->count([])
        );

        // we store some more events to make sure only correct events are retrieved
        $this->testSubject->appendEvents("test", $this->aggregate2->getUncommittedEvents());
        $events = $this->testSubject->readEvents("test", $this->aggregate1->getIdentifier());

        $actualEvents = [];
        $expectedSequenceNumber = 0;

        while ($events->hasNext()) {
            $event = $events->next();
            $actualEvents[] = $event;

            $this->assertEquals(
                $expectedSequenceNumber,
                $event->getScn()
            );

            $expectedSequenceNumber++;
        }

        $this->assertCount($this->aggregate1->getUncommittedEventCount(), $actualEvents);
    }


    public function testStoreAndLoadEvents_WithUpcaster()
    {
        $this->assertNotNull($this->testSubject);
        /* UpcasterChain mockUpcasterChain = mock(UpcasterChain.class);
         when(mockUpcasterChain.upcast(isA(SerializedObject.class), isA(UpcastingContext.class)))
                 .thenAnswer(new Answer<Object>() {
     @Override
                     public Object answer(InvocationOnMock invocation) throws Throwable {
         SerializedObject serializedObject = (SerializedObject) invocation.getArguments()[0];
                         return Arrays.asList(serializedObject, serializedObject);
                     }
                 });

         testSubject.appendEvents("test", aggregate1.getUncommittedEvents());

         testSubject.setUpcasterChain(mockUpcasterChain);

         assertEquals((long) aggregate1.getUncommittedEventCount(),
                      mongoTemplate.domainEventCollection().count());

         // we store some more events to make sure only correct events are retrieved
         testSubject.appendEvents("test", new SimpleDomainEventStream(
                 new GenericDomainEventMessage<Object>(aggregate2.getIdentifier(),
                                                       0,
                                                       new Object(),
                                                       Collections.singletonMap("key", (Object) "Value"))));

         DomainEventStream events = testSubject.readEvents("test", aggregate1.getIdentifier());
         List<DomainEventMessage> actualEvents = new ArrayList<DomainEventMessage>();
         while (events.hasNext()) {
             DomainEventMessage event = events.next();
             event.getPayload();
             event.getMetaData();
             actualEvents.add(event);
         }

         assertEquals(20, actualEvents.size());
         for (int t = 0; t < 20; t = t + 2) {
     assertEquals(actualEvents.get(t).getSequenceNumber(), actualEvents.get(t + 1).getSequenceNumber());
     assertEquals(actualEvents.get(t).getAggregateIdentifier(),
         actualEvents.get(t + 1).getAggregateIdentifier());
     assertEquals(actualEvents.get(t).getMetaData(), actualEvents.get(t + 1).getMetaData());
     assertNotNull(actualEvents.get(t).getPayload());
     assertNotNull(actualEvents.get(t + 1).getPayload());
 }*/
    }


    public function testLoadWithSnapshotEvent()
    {
        $this->testSubject->appendEvents("test", $this->aggregate1->getUncommittedEvents());
        $this->aggregate1->commitEvents();
        $this->testSubject->appendSnapshotEvent("test", $this->aggregate1->createSnapshotEvent());
        $this->aggregate1->changeState();
        $this->testSubject->appendEvents("test", $this->aggregate1->getUncommittedEvents());
        $this->aggregate1->commitEvents();

        $actualEventStream = $this->testSubject->readEvents("test", $this->aggregate1->getIdentifier());
        $domainEvents = [];

        while ($actualEventStream->hasNext()) {
            $domainEvents[] = $actualEventStream->next();
        }

        $this->assertCount(2, $domainEvents);
    }


    public function testLoadPartiallyWithSnapshotEvent()
    {
        $this->testSubject->appendEvents("test", $this->aggregate1->getUncommittedEvents());
        $this->aggregate1->commitEvents();
        $this->testSubject->appendSnapshotEvent("test", $this->aggregate1->createSnapshotEvent());
        $this->aggregate1->changeState();
        $this->testSubject->appendEvents("test", $this->aggregate1->getUncommittedEvents());
        $this->aggregate1->commitEvents();

        $actualEventStream = $this->testSubject->readEventsWithinScn("test", $this->aggregate1->getIdentifier(), 3);
        $domainEvents = [];

        while ($actualEventStream->hasNext()) {
            $domainEvents[] = $actualEventStream->next();
        }

        $this->assertCount(8, $domainEvents);
        $this->assertEquals(3, $domainEvents[0]->getScn());
    }


    public function testLoadPartiallyWithEndWithSnapshotEvent()
    {
        $this->testSubject->appendEvents("test", $this->aggregate1->getUncommittedEvents());
        $this->aggregate1->commitEvents();
        $this->testSubject->appendSnapshotEvent("test", $this->aggregate1->createSnapshotEvent());
        $this->aggregate1->changeState();
        $this->testSubject->appendEvents("test", $this->aggregate1->getUncommittedEvents());
        $this->aggregate1->commitEvents();

        $actualEventStream = $this->testSubject->readEventsWithinScn("test", $this->aggregate1->getIdentifier(), 3, 6);
        $domainEvents = [];

        while ($actualEventStream->hasNext()) {
            $domainEvents[] = $actualEventStream->next();
        }

        $this->assertCount(4, $domainEvents);
        $this->assertEquals(3, $domainEvents[0]->getScn());
    }


    public function testLoadWithMultipleSnapshotEvents()
    {
        $this->testSubject->appendEvents("test", $this->aggregate1->getUncommittedEvents());
        $this->aggregate1->commitEvents();
        $this->testSubject->appendSnapshotEvent("test", $this->aggregate1->createSnapshotEvent());
        $this->aggregate1->changeState();
        $this->testSubject->appendEvents("test", $this->aggregate1->getUncommittedEvents());
        $this->aggregate1->commitEvents();
        $this->testSubject->appendSnapshotEvent("test", $this->aggregate1->createSnapshotEvent());
        $this->aggregate1->changeState();
        $this->testSubject->appendEvents("test", $this->aggregate1->getUncommittedEvents());
        $this->aggregate1->commitEvents();

        $actualEventStream = $this->testSubject->readEvents("test", $this->aggregate1->getIdentifier());
        $domainEvents = [];

        while ($actualEventStream->hasNext()) {
            $domainEvents[] = $actualEventStream->next();
        }

        $this->assertCount(2, $domainEvents);
    }


    public function testInsertDuplicateSnapshot()
    {
        $this->testSubject->appendSnapshotEvent("test", new GenericDomainEventMessage("id1", 1, new \stdClass()));
        try {
            $this->testSubject->appendSnapshotEvent("test", new GenericDomainEventMessage("id1", 1, new \stdClass()));
            $this->fail("Expected concurrency exception");
        } catch (ConcurrencyException $ex) {
            $this->assertRegExp('/SnapshotEvent/', $ex->getMessage());
        }
    }


    /**
     * @expectedException \Governor\Framework\EventStore\EventStreamNotFoundException
     */
    public function testLoadNonExistent()
    {
        $this->testSubject->readEvents("test", Uuid::uuid1()->toString());
    }


    /*
     * @expectedException \Governor\Framework\EventStore\EventStreamNotFoundException
     *
              public function testLoadStream_UpcasterClearsAllFound() {
          testSubject.setUpcasterChain(new UpcasterChain() {
                      @Override
                      public List<SerializedObject> upcast(SerializedObject serializedObject, UpcastingContext upcastingContext) {
              return Collections.emptyList();
          }
                  });
                  final UUID streamId = UUID.randomUUID();
                  testSubject.appendEvents("test", new SimpleDomainEventStream(
                          new GenericDomainEventMessage<String>(streamId, 0, "test")));
                  testSubject.readEvents("test", streamId);
              }
    */


    public function testStoreDuplicateAggregate()
    {
        $this->testSubject->appendEvents(
            "type1",
            new SimpleDomainEventStream(
                [new GenericDomainEventMessage("aggregate1", 0, new \stdClass())]
            )
        );

        try {
            $this->testSubject->appendEvents(
                "type1",
                new SimpleDomainEventStream(
                    [new GenericDomainEventMessage("aggregate1", 0, new \stdClass())]
                )
            );
            $this->fail("Expected exception to be thrown");
        } catch (ConcurrencyException $ex) {
            $this->assertNotNull($ex);
        }
    }


    private function createDomainEvents($numberOfEvents)
    {
        $events = [];

        for ($cc = 0; $cc < $numberOfEvents; $cc++) {
            $events[] = new GenericDomainEventMessage(
                Uuid::uuid1()->toString(),
                $cc,
                new StubStateChangedEvent()
            );
        }

        return $events;
    }


    public function testVisitAllEvents()
    {
        $eventVisitor = $this->getMock(EventVisitorInterface::class);

        $eventVisitor->expects($this->exactly(100))
            ->method('doWithEvent');

        $this->testSubject->appendEvents('type1', new SimpleDomainEventStream($this->createDomainEvents(77)));
        $this->testSubject->appendEvents('type2', new SimpleDomainEventStream($this->createDomainEvents(23)));

        $this->testSubject->visitEvents($eventVisitor);
    }

    /*
               @DirtiesContext
               @Test
               public void testVisitAllEvents_IncludesUnknownEventType() throws Exception {
               EventVisitor eventVisitor = mock(EventVisitor.class);
                   testSubject.appendEvents("test", new SimpleDomainEventStream(createDomainEvents(10)));
                   final GenericDomainEventMessage eventMessage = new GenericDomainEventMessage<String>("test", 0, "test");
                   testSubject.appendEvents("test", new SimpleDomainEventStream(eventMessage));
                   testSubject.appendEvents("test", new SimpleDomainEventStream(createDomainEvents(10)));
                   // we upcast the event to two instances, one of which is an unknown class
                   testSubject.setUpcasterChain(new LazyUpcasterChain(Arrays.<Upcaster>asList(new StubUpcaster())));
                   testSubject.visitEvents(eventVisitor);

                   verify(eventVisitor, times(21)).doWithEvent(isA(DomainEventMessage.class));
               }*/

               /*
               public function testVisitEvents_AfterTimestamp() {
                   $eventVisitor = $this->getMock(EventVisitorInterface::class);

                   DateTimeUtils.setCurrentMillisFixed(new DateTime(2011, 12, 18, 12, 59, 59, 999).getMillis());
                   testSubject.appendEvents("test", new SimpleDomainEventStream(createDomainEvents(11)));
                   DateTime onePM = new DateTime(2011, 12, 18, 13, 0, 0, 0);
                   DateTimeUtils.setCurrentMillisFixed(onePM.getMillis());
                   testSubject.appendEvents("test", new SimpleDomainEventStream(createDomainEvents(12)));
                   DateTimeUtils.setCurrentMillisFixed(new DateTime(2011, 12, 18, 14, 0, 0, 0).getMillis());
                   testSubject.appendEvents("test", new SimpleDomainEventStream(createDomainEvents(13)));
                   DateTimeUtils.setCurrentMillisFixed(new DateTime(2011, 12, 18, 14, 0, 0, 1).getMillis());
                   testSubject.appendEvents("test", new SimpleDomainEventStream(createDomainEvents(14)));
                   DateTimeUtils.setCurrentMillisSystem();

                   CriteriaBuilder criteriaBuilder = testSubject.newCriteriaBuilder();
                   testSubject.visitEvents(criteriaBuilder.property("timeStamp").greaterThan(onePM), eventVisitor);
                   ArgumentCaptor<DomainEventMessage> captor = ArgumentCaptor.forClass(DomainEventMessage.class);
                   verify(eventVisitor, times(13 + 14)).doWithEvent(captor.capture());
                   assertEquals(new DateTime(2011, 12, 18, 14, 0, 0, 0), captor.getAllValues().get(0).getTimestamp());
                   assertEquals(new DateTime(2011, 12, 18, 14, 0, 0, 1), captor.getAllValues().get(26).getTimestamp());
               }

               /*
               public void testVisitEvents_BetweenTimestamps() {
           EventVisitor eventVisitor = mock(EventVisitor.class);
                   DateTimeUtils.setCurrentMillisFixed(new DateTime(2011, 12, 18, 12, 59, 59, 999).getMillis());
                   testSubject.appendEvents("test", new SimpleDomainEventStream(createDomainEvents(11)));
                   DateTime onePM = new DateTime(2011, 12, 18, 13, 0, 0, 0);
                   DateTimeUtils.setCurrentMillisFixed(onePM.getMillis());
                   testSubject.appendEvents("test", new SimpleDomainEventStream(createDomainEvents(12)));
                   DateTime twoPM = new DateTime(2011, 12, 18, 14, 0, 0, 0);
                   DateTimeUtils.setCurrentMillisFixed(twoPM.getMillis());
                   testSubject.appendEvents("test", new SimpleDomainEventStream(createDomainEvents(13)));
                   DateTimeUtils.setCurrentMillisFixed(new DateTime(2011, 12, 18, 14, 0, 0, 1).getMillis());
                   testSubject.appendEvents("test", new SimpleDomainEventStream(createDomainEvents(14)));
                   DateTimeUtils.setCurrentMillisSystem();

                   CriteriaBuilder criteriaBuilder = testSubject.newCriteriaBuilder();
                   testSubject.visitEvents(criteriaBuilder.property("timeStamp").greaterThanEquals(onePM)
                       .and(criteriaBuilder.property("timeStamp").lessThanEquals(twoPM)),
                                           eventVisitor);
                   verify(eventVisitor, times(12 + 13)).doWithEvent(isA(DomainEventMessage.class));
               }

               @DirtiesContext
               @Test
               public void testVisitEvents_OnOrAfterTimestamp() {
           EventVisitor eventVisitor = mock(EventVisitor.class);
                   DateTimeUtils.setCurrentMillisFixed(new DateTime(2011, 12, 18, 12, 59, 59, 999).getMillis());
                   testSubject.appendEvents("test", new SimpleDomainEventStream(createDomainEvents(11)));
                   DateTime onePM = new DateTime(2011, 12, 18, 13, 0, 0, 0);
                   DateTimeUtils.setCurrentMillisFixed(onePM.getMillis());
                   testSubject.appendEvents("test", new SimpleDomainEventStream(createDomainEvents(12)));
                   DateTimeUtils.setCurrentMillisFixed(new DateTime(2011, 12, 18, 14, 0, 0, 0).getMillis());
                   testSubject.appendEvents("test", new SimpleDomainEventStream(createDomainEvents(13)));
                   DateTimeUtils.setCurrentMillisFixed(new DateTime(2011, 12, 18, 14, 0, 0, 1).getMillis());
                   testSubject.appendEvents("test", new SimpleDomainEventStream(createDomainEvents(14)));
                   DateTimeUtils.setCurrentMillisSystem();

                   CriteriaBuilder criteriaBuilder = testSubject.newCriteriaBuilder();
                   testSubject.visitEvents(criteriaBuilder.property("timeStamp").greaterThanEquals(onePM), eventVisitor);
                   verify(eventVisitor, times(12 + 13 + 14)).doWithEvent(isA(DomainEventMessage.class));
               }

        /*
            private static class StubUpcaster implements Upcaster<byte[]> {

            @Override
                public boolean canUpcast(SerializedType serializedType) {
                return "java.lang.String".equals(serializedType.getName());
            }

                @Override
                public Class<byte[]> expectedRepresentationType() {
                    return byte[].class;
                }

                @Override
                public List<SerializedObject<?>> upcast(SerializedObject<byte[]> intermediateRepresentation,
                                                        List<SerializedType> expectedTypes, UpcastingContext context) {
                return Arrays.<SerializedObject<?>>asList(
                new SimpleSerializedObject<String>("data1", String.class, expectedTypes.get(0)),
                            new SimpleSerializedObject<byte[]>(intermediateRepresentation.getData(), byte[].class,
                                                               expectedTypes.get(1)));
                }

                @Override
                public List<SerializedType> upcast(SerializedType serializedType) {
            return Arrays.<SerializedType>asList(new SimpleSerializedType("unknownType1", "2"),
                new SimpleSerializedType(StubStateChangedEvent.class.getName(), "2"));
                }
            }*/
}

class StubAggregateRoot extends AbstractAnnotatedAggregateRoot
{

    private $identifier;


    public function  __construct()
    {
        $this->identifier = Uuid::uuid1()->toString();
    }

    public function changeState()
    {
        $this->apply(new StubStateChangedEvent());
    }


    public function getIdentifier()
    {
        return $this->identifier;
    }


    /**
     * @Governor\EventHandler()
     */
    public function handleStateChange(StubStateChangedEvent $event)
    {

    }

    public function createSnapshotEvent()
    {
        return new GenericDomainEventMessage(
            $this->getIdentifier(), $this->getVersion(), new StubStateChangedEvent(), null
        );
    }
}

class StubStateChangedEvent
{

}