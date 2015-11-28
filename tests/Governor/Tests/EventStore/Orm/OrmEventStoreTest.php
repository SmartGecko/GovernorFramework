<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Tests\EventStore\Orm;

use Ramsey\Uuid\Uuid;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\Common\Annotations\AnnotationReader;
use Governor\Framework\EventStore\Orm\Criteria\OrmCriteria;
use Governor\Framework\EventStore\EventVisitorInterface;
use Governor\Framework\Serializer\JMSSerializer;
use Governor\Framework\Domain\MetaData;
use Governor\Framework\Domain\SimpleDomainEventStream;
use Governor\Framework\Serializer\NullRevisionResolver;
use Governor\Framework\Serializer\SimpleSerializedObject;
use Governor\Framework\Serializer\SimpleSerializedType;
use Governor\Framework\Annotations\EventSourcingHandler;
use Governor\Framework\EventSourcing\Annotation\AbstractAnnotatedAggregateRoot;
use Governor\Framework\Domain\GenericDomainEventMessage;
use Governor\Framework\EventStore\Orm\OrmEventStore;
use Governor\Framework\EventStore\Orm\DomainEventEntry;

/**
 * Description of OrmEventStoreTest
 *
 * @author david
 */
class OrmEventStoreTest extends \PHPUnit_Framework_TestCase
{

    private $testSubject;
    private static $config;
    private static $dbParams;
    private $entityManager;
    private $aggregate1;
    private $aggregate2;

    public static function setUpBeforeClass()
    {
        // bootstrap doctrine
        self::$dbParams = array(
            'driver' => 'pdo_sqlite',
            'user' => 'root',
            'password' => '',
            'memory' => true
        );

        self::$config = Setup::createConfiguration(true);
        self::$config->setMetadataDriverImpl(new AnnotationDriver(new AnnotationReader()));
        //self::$config->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger());
    }

    public function setUp()
    {
        $this->entityManager = EntityManager::create(self::$dbParams,
                        self::$config);

        $tool = new \Doctrine\ORM\Tools\SchemaTool($this->entityManager);
        $classes = array(
            $this->entityManager->getClassMetadata(\Governor\Framework\EventStore\Orm\DomainEventEntry::class),
            $this->entityManager->getClassMetadata(\Governor\Framework\EventStore\Orm\SnapshotEventEntry::class)
        );

        $tool->createSchema($classes);

        $this->testSubject = new OrmEventStore($this->entityManager,
                new JMSSerializer(new NullRevisionResolver()));

        $this->aggregate1 = new StubAggregateRoot(Uuid::uuid1()->toString());
        for ($t = 0; $t < 10; $t++) {
            $this->aggregate1->changeState();
        }

        $this->aggregate2 = new StubAggregateRoot();
        $this->aggregate2->changeState();
        $this->aggregate2->changeState();
        $this->aggregate2->changeState();
    }

    public function tearDown()
    {
        
    }

    /**
     * @expectedException \Doctrine\DBAL\DBALException
     */
    public function testUniqueKeyConstraintOnEventIdentifier()
    {
        $emptySerializedObject = new SimpleSerializedObject('{}',
                new SimpleSerializedType('stdClass'));

        //$firstEvent = $this->aggregate2->getUncommittedEvents()->next();        
        $this->entityManager->persist(new DomainEventEntry("type",
                new GenericDomainEventMessage("someValue", 0,
                $emptySerializedObject, MetaData::emptyInstance(), "a",
                new \DateTime()), $emptySerializedObject, $emptySerializedObject));

        $this->entityManager->flush();

        $this->entityManager->persist(new DomainEventEntry("type",
                new GenericDomainEventMessage("anotherValue", 0,
                $emptySerializedObject, MetaData::emptyInstance(), "a",
                new \DateTime()), $emptySerializedObject, $emptySerializedObject));

        $this->entityManager->flush();
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testStoreAndLoadEvents_BadIdentifierType()
    {
        $this->testSubject->appendEvents("type",
                new SimpleDomainEventStream(array(
            new GenericDomainEventMessage(new \stdClass(), 1, new \stdClass()))));
    }

    /**
     * @expectedException \Governor\Framework\Serializer\UnknownSerializedTypeException
     */
    public function testUnknownSerializedTypeCausesException()
    {
        $this->testSubject->appendEvents("type",
                $this->aggregate1->getUncommittedEvents());
        $this->entityManager->flush();
        $this->entityManager->clear();

        $this->entityManager->createQuery("UPDATE Governor\Framework\EventStore\Orm\DomainEventEntry e SET e.payloadType = :type")
                ->setParameter(":type", "unknown")->execute();

        $this->testSubject->readEvents("type",
                $this->aggregate1->getIdentifier());
    }

    public function testStoreAndLoadEvents()
    {
        $this->assertNotNull($this->testSubject);

        $this->testSubject->appendEvents("test",
                $this->aggregate1->getUncommittedEvents());
        $this->entityManager->flush();

        $this->assertEquals($this->aggregate1->getUncommittedEventCount(),
                $this->entityManager->createQuery("SELECT count(e) FROM Governor\Framework\EventStore\Orm\DomainEventEntry e")->getSingleScalarResult());

        // we store some more events to make sure only correct events are retrieved
        $this->testSubject->appendEvents("test",
                new SimpleDomainEventStream(array(
            new GenericDomainEventMessage($this->aggregate2->getIdentifier(), 0,
                    new \stdClass(), new MetaData(array("key" => "Value"))
        ))));

        $this->entityManager->flush();
        $this->entityManager->clear();

        $events = $this->testSubject->readEvents("test",
                $this->aggregate1->getIdentifier());
        $actualEvents = array();

        while ($events->hasNext()) {
            $event = $events->next();
            $actualEvents[] = $event;
        }
        $this->assertEquals($this->aggregate1->getUncommittedEventCount(),
                count($actualEvents));

        /// we make sure persisted events have the same MetaData alteration logic
        $other = $this->testSubject->readEvents("test",
                $this->aggregate2->getIdentifier());
        $this->assertTrue($other->hasNext());
        $messageWithMetaData = $other->next();

        $altered = $messageWithMetaData->withMetaData(array("key2" => "value"));
        $combined = $messageWithMetaData->andMetaData(array("key2" => "value"));
        $this->assertTrue($altered->getMetaData()->has("key2"));
        $this->assertFalse($altered->getMetaData()->has("key"));
        $this->assertTrue($altered->getMetaData()->has("key2"));
        $this->assertTrue($combined->getMetaData()->has("key"));
        $this->assertTrue($combined->getMetaData()->has("key2"));
        $this->assertNotNull($messageWithMetaData->getPayload());
        $this->assertNotNull($messageWithMetaData->getMetaData());
        $this->assertFalse($messageWithMetaData->getMetaData()->isEmpty());
    }

    /*
      @DirtiesContext
      @Test
      @Transactional
      public void testStoreAndLoadEvents_WithUpcaster() {
      assertNotNull(testSubject);
      UpcasterChain mockUpcasterChain = mock(UpcasterChain.class);
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
      entityManager.flush();
      assertEquals((long) aggregate1.getUncommittedEventCount(),
      entityManager.createQuery("SELECT count(e) FROM DomainEventEntry e").getSingleResult());

      // we store some more events to make sure only correct events are retrieved
      testSubject.appendEvents("test", new SimpleDomainEventStream(
      new GenericDomainEventMessage<Object>(aggregate2.getIdentifier(),
      0,
      new Object(),
      Collections.singletonMap("key", (Object) "Value"))));
      entityManager.flush();
      entityManager.clear();

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
      }
      } */

    public function testLoad_LargeAmountOfEvents()
    {
        $domainEvents = array();
        $aggregateIdentifier = "id";
        for ($cc = 0; $cc < 110; $cc++) {
            $domainEvents[] = new GenericDomainEventMessage($aggregateIdentifier,
                    $cc, new \stdClass(), MetaData::emptyInstance());
        }

        $this->testSubject->appendEvents("test",
                new SimpleDomainEventStream($domainEvents));
        $this->entityManager->flush();
        $this->entityManager->clear();

        $events = $this->testSubject->readEvents("test", $aggregateIdentifier);
        $cc = 0;
        while ($events->hasNext()) {
            $event = $events->next();
            $this->assertEquals($cc, $event->getScn());
            $cc++;
        }
        $this->assertEquals(110, $cc);
    }

    public function testLoad_LargeAmountOfEventsInSmallBatches()
    {
        $this->testSubject->setBatchSize(10);
        $this->testLoad_LargeAmountOfEvents();
    }

    /*
      @Test
      @Transactional
      public void testEntireStreamIsReadOnUnserializableSnapshot_WithException() {
      List<DomainEventMessage<String>> domainEvents = new ArrayList<DomainEventMessage<String>>(110);
      String aggregateIdentifier = "id";
      for (int t = 0; t < 110; t++) {
      domainEvents.add(new GenericDomainEventMessage<String>(aggregateIdentifier, (long) t,
      "Mock contents", MetaData.emptyInstance()));
      }
      testSubject.appendEvents("test", new SimpleDomainEventStream(domainEvents));
      final Serializer serializer = new Serializer() {

      private ChainingConverterFactory converterFactory = new ChainingConverterFactory();

      @SuppressWarnings("unchecked")
      @Override
      public <T> SerializedObject<T> serialize(Object object, Class<T> expectedType) {
      Assert.assertEquals(byte[].class, expectedType);
      return new SimpleSerializedObject("this ain't gonna work".getBytes(), byte[].class, "failingType", "0");
      }

      @Override
      public <T> boolean canSerializeTo(Class<T> expectedRepresentation) {
      return byte[].class.equals(expectedRepresentation);
      }

      @Override
      public <S, T> T deserialize(SerializedObject<S> serializedObject) {
      throw new UnsupportedOperationException("Not implemented yet");
      }

      @Override
      public Class classForType(SerializedType type) {
      try {
      return Class.forName(type.getName());
      } catch (ClassNotFoundException e) {
      return null;
      }
      }

      @Override
      public SerializedType typeForClass(Class type) {
      return new SimpleSerializedType(type.getName(), "");
      }

      @Override
      public ConverterFactory getConverterFactory() {
      return converterFactory;
      }
      };
      final DomainEventMessage<String> stubDomainEvent = new GenericDomainEventMessage<String>(
      aggregateIdentifier,
      (long) 30,
      "Mock contents", MetaData.emptyInstance()
      );
      SnapshotEventEntry entry = new SnapshotEventEntry(
      "test", stubDomainEvent,
      serializer.serialize(stubDomainEvent.getPayload(), byte[].class),
      serializer.serialize(stubDomainEvent.getMetaData(), byte[].class));
      entityManager.persist(entry);
      entityManager.flush();
      entityManager.clear();

      DomainEventStream stream = testSubject.readEvents("test", aggregateIdentifier);
      assertEquals(0L, stream.peek().getSequenceNumber());
      }


      public void testEntireStreamIsReadOnUnserializableSnapshot_WithError() {
      List<DomainEventMessage<String>> domainEvents = new ArrayList<DomainEventMessage<String>>(110);
      String aggregateIdentifier = "id";
      for (int t = 0; t < 110; t++) {
      domainEvents.add(new GenericDomainEventMessage<String>(aggregateIdentifier, (long) t,
      "Mock contents", MetaData.emptyInstance()));
      }
      testSubject.appendEvents("test", new SimpleDomainEventStream(domainEvents));
      final Serializer serializer = new Serializer() {

      private ConverterFactory converterFactory = new ChainingConverterFactory();

      @SuppressWarnings("unchecked")
      @Override
      public <T> SerializedObject<T> serialize(Object object, Class<T> expectedType) {
      // this will cause InstantiationError, since it is an interface
      Assert.assertEquals(byte[].class, expectedType);
      return new SimpleSerializedObject("<org.axonframework.eventhandling.EventListener />".getBytes(),
      byte[].class,
      "failingType",
      "0");
      }

      @Override
      public <T> boolean canSerializeTo(Class<T> expectedRepresentation) {
      return byte[].class.equals(expectedRepresentation);
      }

      @Override
      public <S, T> T deserialize(SerializedObject<S> serializedObject) {
      throw new UnsupportedOperationException("Not implemented yet");
      }

      @Override
      public Class classForType(SerializedType type) {
      try {
      return Class.forName(type.getName());
      } catch (ClassNotFoundException e) {
      return null;
      }
      }

      @Override
      public SerializedType typeForClass(Class type) {
      return new SimpleSerializedType(type.getName(), "");
      }

      @Override
      public ConverterFactory getConverterFactory() {
      return converterFactory;
      }
      };
      final DomainEventMessage<String> stubDomainEvent = new GenericDomainEventMessage<String>(
      aggregateIdentifier,
      (long) 30,
      "Mock contents", MetaData.emptyInstance()
      );
      SnapshotEventEntry entry = new SnapshotEventEntry(
      "test", stubDomainEvent,
      serializer.serialize(stubDomainEvent.getPayload(), byte[].class),
      serializer.serialize(stubDomainEvent.getMetaData(), byte[].class));
      entityManager.persist(entry);
      entityManager.flush();
      entityManager.clear();

      DomainEventStream stream = testSubject.readEvents("test", aggregateIdentifier);
      assertEquals(0L, stream.peek().getSequenceNumber());
      } */

    public function testLoad_LargeAmountOfEventsWithSnapshot()
    {
        $domainEvents = array();
        $aggregateIdentifier = "id";
        for ($cc = 0; $cc < 110; $cc++) {
            $domainEvents[] = new GenericDomainEventMessage($aggregateIdentifier,
                    $cc, new \stdClass(), MetaData::emptyInstance());
        }

        $this->testSubject->appendEvents("test",
                new SimpleDomainEventStream($domainEvents));
        $this->testSubject->appendSnapshotEvent("test",
                new GenericDomainEventMessage($aggregateIdentifier, 30,
                new \stdClass(), MetaData::emptyInstance()
        ));

        $this->entityManager->flush();
        $this->entityManager->clear();

        $events = $this->testSubject->readEvents("test", $aggregateIdentifier);
        $cc = 30;

        while ($events->hasNext()) {
            $event = $events->next();
            $this->assertEquals($cc, $event->getScn());
            $cc++;
        }
        $this->assertEquals(110, $cc);
    }

    public function testLoadWithSnapshotEvent()
    {
        $this->testSubject->appendEvents("test",
                $this->aggregate1->getUncommittedEvents());
        $this->aggregate1->commitEvents();
        $this->entityManager->flush();
        $this->entityManager->clear();

        $this->testSubject->appendSnapshotEvent("test",
                $this->aggregate1->createSnapshotEvent());
        $this->entityManager->flush();
        $this->entityManager->clear();

        $this->aggregate1->changeState();
        $this->testSubject->appendEvents("test",
                $this->aggregate1->getUncommittedEvents());
        $this->aggregate1->commitEvents();

        $actualEventStream = $this->testSubject->readEvents("test",
                $this->aggregate1->getIdentifier());
        $domainEvents = array();

        while ($actualEventStream->hasNext()) {
            $next = $actualEventStream->next();
            $domainEvents[] = $next;
            $this->assertEquals($this->aggregate1->getIdentifier(),
                    $next->getAggregateIdentifier());
        }

        $this->assertEquals(2, count($domainEvents));
    }

    /**
     * @expectedException \Governor\Framework\EventStore\EventStreamNotFoundException
     */
    public function testLoadNonExistent()
    {
        $stream = $this->testSubject->readEvents("Stub",
                Uuid::uuid1()->toString());
    }

    public function testVisitAllEvents()
    {
        $criteria = \Phake::mock(OrmCriteria::class);
        $eventVisitor = \Phake::mock(EventVisitorInterface::class);

        $this->testSubject->appendEvents("test",
                new SimpleDomainEventStream($this->createDomainEvents(77)));
        $this->testSubject->appendEvents("test",
                new SimpleDomainEventStream($this->createDomainEvents(23)));

        $this->testSubject->visitEvents($eventVisitor, $criteria);

        \Phake::verify($eventVisitor, \Phake::times(100))->doWithEvent(\Phake::anyParameters());
    }

    public function testVisitAllEvents_IncludesUnknownEventType()
    {
        $eventVisitor = \Phake::mock(EventVisitorInterface::class);

        $this->testSubject->appendEvents("test",
                new SimpleDomainEventStream($this->createDomainEvents(10)));

        $eventMessage = new GenericDomainEventMessage("test", 0, new \stdClass());

        $this->testSubject->appendEvents("test",
                new SimpleDomainEventStream(array($eventMessage)));
        $this->testSubject->appendEvents("test",
                new SimpleDomainEventStream($this->createDomainEvents(10)));
        // we upcast the event to two instances, one of which is an unknown class
        //$this->testSubject->setUpcasterChain(new LazyUpcasterChain(Arrays.<Upcaster>asList(new StubUpcaster())));
        $this->testSubject->visitEvents($eventVisitor);

        \Phake::verify($eventVisitor, \Phake::times(21))->doWithEvent(\Phake::anyParameters());
    }

    public function testVisitEvents_AfterTimestamp()
    {
        $eventVisitor = \Phake::mock(EventVisitorInterface::class);

        $this->testSubject->appendEvents("test",
                new SimpleDomainEventStream($this->createDomainEvents(11)));
        sleep(1);
        $this->testSubject->appendEvents("test",
                new SimpleDomainEventStream($this->createDomainEvents(12)));
        sleep(1);

        $now = new \DateTime();
        sleep(1);
        
        $this->testSubject->appendEvents("test",
                new SimpleDomainEventStream($this->createDomainEvents(13)));

        $this->testSubject->appendEvents("test",
                new SimpleDomainEventStream($this->createDomainEvents(14)));

        $criteriaBuilder = $this->testSubject->newCriteriaBuilder();
        $this->testSubject->visitEvents($eventVisitor,
                $criteriaBuilder->property("timestamp")->greaterThan($now));

        \Phake::verify($eventVisitor, \Phake::times(13 + 14))->doWithEvent(\Phake::anyParameters());
    }

    /*
      @Test
      @Transactional
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

      @Test
      @Transactional
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

      @Test(expected = ConcurrencyException.class)
      @Transactional
      public void testStoreDuplicateEvent_WithSqlExceptionTranslator() {
      testSubject.appendEvents("test", new SimpleDomainEventStream(
      new GenericDomainEventMessage<String>("123", 0L,
      "Mock contents", MetaData.emptyInstance())));
      entityManager.flush();
      entityManager.clear();
      testSubject.appendEvents("test", new SimpleDomainEventStream(
      new GenericDomainEventMessage<String>("123", 0L,
      "Mock contents", MetaData.emptyInstance())));
      }

      @DirtiesContext
      @Test
      @Transactional
      public void testStoreDuplicateEvent_NoSqlExceptionTranslator() {
      testSubject.setPersistenceExceptionResolver(null);
      try {
      testSubject.appendEvents("test", new SimpleDomainEventStream(
      new GenericDomainEventMessage<String>("123", (long) 0,
      "Mock contents", MetaData.emptyInstance())));
      entityManager.flush();
      entityManager.clear();
      testSubject.appendEvents("test", new SimpleDomainEventStream(
      new GenericDomainEventMessage<String>("123", (long) 0,
      "Mock contents", MetaData.emptyInstance())));
      } catch (ConcurrencyException ex) {
      fail("Didn't expect exception to be translated");
      } catch (Exception ex) {
      final StringWriter writer = new StringWriter();
      ex.printStackTrace(new PrintWriter(writer));
      assertTrue("Got the right exception, "
      + "but the message doesn't seem to mention 'DomainEventEntry': " + ex.getMessage(),
      writer.toString().toLowerCase().contains("domainevententry"));
      }
      } */

    public function testPrunesSnaphotsWhenNumberOfSnapshotsExceedsConfiguredMaxSnapshotsArchived()
    {
        $this->testSubject->setMaxSnapshotsArchived(1);

        $aggregate = new StubAggregateRoot();

        $aggregate->changeState();
        $this->testSubject->appendEvents("type",
                $aggregate->getUncommittedEvents());
        $aggregate->commitEvents();
        $this->entityManager->flush();
        $this->entityManager->clear();

        $this->testSubject->appendSnapshotEvent("type",
                $aggregate->createSnapshotEvent());
        $this->entityManager->flush();
        $this->entityManager->clear();

        $aggregate->changeState();
        $this->testSubject->appendEvents("type",
                $aggregate->getUncommittedEvents());
        $aggregate->commitEvents();
        $this->entityManager->flush();
        $this->entityManager->clear();

        $this->testSubject->appendSnapshotEvent("type",
                $aggregate->createSnapshotEvent());
        $this->entityManager->flush();
        $this->entityManager->clear();

        $snapshots = $this->entityManager->createQuery("SELECT e FROM Governor\Framework\EventStore\Orm\SnapshotEventEntry e " .
                        "WHERE e.type = :type " .
                        "AND e.aggregateIdentifier = :aggregateIdentifier")
                ->setParameters(array(
                    ":type" => "type",
                    ":aggregateIdentifier" => $aggregate->getIdentifier()
                ))
                ->getResult();

        $this->assertCount(1, $snapshots);
        $this->assertEquals(1, $snapshots[0]->getScn());
    }

    /*
      @SuppressWarnings({"PrimitiveArrayArgumentToVariableArgMethod", "unchecked"})
      @DirtiesContext
      @Test
      @Transactional
      public void testCustomEventEntryStore() {
      EventEntryStore eventEntryStore = mock(EventEntryStore.class);
      testSubject = new JpaEventStore(new SimpleEntityManagerProvider(entityManager), eventEntryStore);
      testSubject.appendEvents("test", new SimpleDomainEventStream(
      new GenericDomainEventMessage<String>(UUID.randomUUID(), (long) 0,
      "Mock contents", MetaData.emptyInstance()),
      new GenericDomainEventMessage<String>(UUID.randomUUID(), (long) 0,
      "Mock contents", MetaData.emptyInstance())));
      verify(eventEntryStore, times(2)).persistEvent(eq("test"), isA(DomainEventMessage.class),
      Matchers.<SerializedObject>any(),
      Matchers.<SerializedObject>any(), same(entityManager));

      reset(eventEntryStore);
      GenericDomainEventMessage<String> eventMessage = new GenericDomainEventMessage<String>(
      UUID.randomUUID(), 0L, "Mock contents", MetaData.emptyInstance());
      when(eventEntryStore.fetchAggregateStream(anyString(), any(), anyInt(), anyInt(),
      any(EntityManager.class)))
      .thenReturn(new ArrayList(Arrays.asList(new DomainEventEntry(
      "Mock", eventMessage,
      mockSerializedObject("Mock contents".getBytes()),
      mockSerializedObject("Mock contents".getBytes())))).iterator());
      when(eventEntryStore.loadLastSnapshotEvent(anyString(), any(),
      any(EntityManager.class)))
      .thenReturn(null);

      testSubject.readEvents("test", "1");

      verify(eventEntryStore).fetchAggregateStream("test", "1", 0, 100, entityManager);
      verify(eventEntryStore).loadLastSnapshotEvent("test", "1", entityManager);
      }

      @Test
      @Transactional
      public void testReadPartialStream_WithoutEnd() {
      final UUID aggregateIdentifier = UUID.randomUUID();
      testSubject.appendEvents("test", new SimpleDomainEventStream(
      new GenericDomainEventMessage<String>(aggregateIdentifier, (long) 0,
      "Mock contents", MetaData.emptyInstance()),
      new GenericDomainEventMessage<String>(aggregateIdentifier, (long) 1,
      "Mock contents", MetaData.emptyInstance()),
      new GenericDomainEventMessage<String>(aggregateIdentifier, (long) 2,
      "Mock contents", MetaData.emptyInstance()),
      new GenericDomainEventMessage<String>(aggregateIdentifier, (long) 3,
      "Mock contents", MetaData.emptyInstance()),
      new GenericDomainEventMessage<String>(aggregateIdentifier, (long) 4,
      "Mock contents", MetaData.emptyInstance())));
      testSubject.appendSnapshotEvent("test", new GenericDomainEventMessage<String>(aggregateIdentifier,
      (long) 3,
      "Mock contents",
      MetaData.emptyInstance()));

      entityManager.flush();
      entityManager.clear();

      DomainEventStream actual = testSubject.readEvents("test", aggregateIdentifier, 2);
      for (int i=2;i<=4;i++) {
      assertTrue(actual.hasNext());
      assertEquals(i, actual.next().getSequenceNumber());
      }
      assertFalse(actual.hasNext());
      }

      @Test
      @Transactional
      public void testReadPartialStream_WithEnd() {
      final UUID aggregateIdentifier = UUID.randomUUID();
      testSubject.appendEvents("test", new SimpleDomainEventStream(
      new GenericDomainEventMessage<String>(aggregateIdentifier, (long) 0,
      "Mock contents", MetaData.emptyInstance()),
      new GenericDomainEventMessage<String>(aggregateIdentifier, (long) 1,
      "Mock contents", MetaData.emptyInstance()),
      new GenericDomainEventMessage<String>(aggregateIdentifier, (long) 2,
      "Mock contents", MetaData.emptyInstance()),
      new GenericDomainEventMessage<String>(aggregateIdentifier, (long) 3,
      "Mock contents", MetaData.emptyInstance()),
      new GenericDomainEventMessage<String>(aggregateIdentifier, (long) 4,
      "Mock contents", MetaData.emptyInstance())));

      testSubject.appendSnapshotEvent("test", new GenericDomainEventMessage<String>(aggregateIdentifier,
      (long) 3,
      "Mock contents",
      MetaData.emptyInstance()));

      entityManager.flush();
      entityManager.clear();

      DomainEventStream actual = testSubject.readEvents("test", aggregateIdentifier, 2, 3);
      for (int i=2;i<=3;i++) {
      assertTrue(actual.hasNext());
      assertEquals(i, actual.next().getSequenceNumber());
      }
      assertFalse(actual.hasNext());
      }

      private SerializedObject<byte[]> mockSerializedObject(byte[] bytes) {
      return new SimpleSerializedObject<byte[]>(bytes, byte[].class, "java.lang.String", "0");
      } */

    private function createDomainEvents($numberOfEvents)
    {
        $events = array();
        $aggregateIdentifier = Uuid::uuid1()->toString();

        for ($t = 0; $t < $numberOfEvents; $t++) {
            $events[] = new GenericDomainEventMessage(
                    $aggregateIdentifier, $t, new StubStateChangedEvent(),
                    MetaData::emptyInstance()
            );
        }

        return $events;
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
      }

     */
}

class StubAggregateRoot extends AbstractAnnotatedAggregateRoot
{

    private $identifier;

    public function __construct($identifier = null)
    {
        $this->identifier = (null !== $identifier) ? $identifier : Uuid::uuid1()->toString();
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
     * @EventSourcingHandler
     */
    public function handleStateChange(StubStateChangedEvent $event)
    {
        
    }

    public function createSnapshotEvent()
    {
        return new GenericDomainEventMessage($this->getIdentifier(),
                $this->getVersion(), new StubStateChangedEvent(),
                MetaData::emptyInstance()
        );
    }

}

class StubStateChangedEvent
{
    
}
