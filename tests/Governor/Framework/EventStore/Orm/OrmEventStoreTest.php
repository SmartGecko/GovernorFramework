<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\EventStore\Orm;

use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use Rhumsaa\Uuid\Uuid;
use Governor\Framework\Serializer\JMSSerializer;
use Governor\Framework\Domain\MetaData;
use Governor\Framework\Domain\SimpleDomainEventStream;
use Governor\Framework\Serializer\NullRevisionResolver;
use Governor\Framework\Serializer\SimpleSerializedObject;
use Governor\Framework\Serializer\SimpleSerializedType;
use Governor\Framework\Annotations\EventSourcingHandler;
use Governor\Framework\EventSourcing\Annotation\AbstractAnnotatedAggregateRoot;
use Governor\Framework\Domain\GenericDomainEventMessage;

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

    /*  @Autowired
      private PlatformTransactionManager txManager;
      private TransactionTemplate template; */

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
        self::$config->setMetadataDriverImpl(new \Doctrine\ORM\Mapping\Driver\SimplifiedXmlDriver(self::getMappingDirectories()));
       // self::$config->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger());
    }

    public function setUp()
    {
        $this->entityManager = EntityManager::create(self::$dbParams,
                        self::$config);

        $tool = new \Doctrine\ORM\Tools\SchemaTool($this->entityManager);
        $classes = array($this->entityManager->getClassMetadata('Governor\Framework\EventStore\Orm\DomainEventEntry'),
            $this->entityManager->getClassMetadata('Governor\Framework\EventStore\Orm\SnapshotEventEntry'));

        $tool->createSchema($classes);

        $this->testSubject = new OrmEventStore($this->entityManager,
                new JMSSerializer(new NullRevisionResolver()));

        // template = new TransactionTemplate(txManager);
        $this->aggregate1 = new StubAggregateRoot(Uuid::uuid1()->toString());
        for ($t = 0; $t < 10; $t++) {
            $this->aggregate1->changeState();
        }

        $this->aggregate2 = new StubAggregateRoot();
        $this->aggregate2->changeState();
        $this->aggregate2->changeState();
        $this->aggregate2->changeState();
        /*
          template.execute(new TransactionCallbackWithoutResult() {
          @Override
          protected void doInTransactionWithoutResult(TransactionStatus status) {
          entityManager.createQuery("DELETE FROM DomainEventEntry").executeUpdate();
          }
          }); */
    }

    private static function getMappingDirectories()
    {

        $path = array('..', '..', '..', '..', '..', 'src',
            'Governor', 'Framework', 'Plugin', 'SymfonyBundle', 'Resources', 'config',
            'doctrine');

        return array(
            // __DIR__ . DIRECTORY_SEPARATOR . join(DIRECTORY_SEPARATOR, $path)
            'C:\XXX'
            => 'Governor\Framework'
        );
    }

    public function tearDown()
    {
        // just to make sure
        //DateTimeUtils.setCurrentMillisSystem();
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

    /*
      @Transactional
      @Test(expected = UnknownSerializedTypeException.class)
      public void testUnknownSerializedTypeCausesException() {
      testSubject.appendEvents("type", aggregate1.getUncommittedEvents());
      entityManager.flush();
      entityManager.clear();
      entityManager.createQuery("UPDATE DomainEventEntry e SET e.payloadType = :type")
      .setParameter("type", "unknown")
      .executeUpdate();

      testSubject.readEvents("type", aggregate1.getIdentifier());
      } */

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
            //event.getPayload();
            //event.getMetaData();
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
        //altered . getPayload();
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
      }

      @Test
      @Transactional
      public void testLoad_LargeAmountOfEvents() {
      List<DomainEventMessage<String>> domainEvents = new ArrayList<DomainEventMessage<String>>(110);
      String aggregateIdentifier = "id";
      for (int t = 0; t < 110; t++) {
      domainEvents.add(new GenericDomainEventMessage<String>(aggregateIdentifier, (long) t,
      "Mock contents", MetaData.emptyInstance()));
      }
      testSubject.appendEvents("test", new SimpleDomainEventStream(domainEvents));
      entityManager.flush();
      entityManager.clear();

      DomainEventStream events = testSubject.readEvents("test", aggregateIdentifier);
      long t = 0L;
      while (events.hasNext()) {
      DomainEventMessage event = events.next();
      assertEquals(t, event.getSequenceNumber());
      t++;
      }
      assertEquals(110L, t);
      }

      @DirtiesContext
      @Test
      @Transactional
      public void testLoad_LargeAmountOfEventsInSmallBatches() {
      testSubject.setBatchSize(10);
      testLoad_LargeAmountOfEvents();
      }

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

      @Test
      @Transactional
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
      }

      @Test
      @Transactional
      public void testLoad_LargeAmountOfEventsWithSnapshot() {
      List<DomainEventMessage<String>> domainEvents = new ArrayList<DomainEventMessage<String>>(110);
      String aggregateIdentifier = "id";
      for (int t = 0; t < 110; t++) {
      domainEvents.add(new GenericDomainEventMessage<String>(aggregateIdentifier, (long) t,
      "Mock contents", MetaData.emptyInstance()));
      }
      testSubject.appendEvents("test", new SimpleDomainEventStream(domainEvents));
      testSubject.appendSnapshotEvent("test", new GenericDomainEventMessage<String>(aggregateIdentifier, (long) 30,
      "Mock contents",
      MetaData.emptyInstance()
      ));
      entityManager.flush();
      entityManager.clear();

      DomainEventStream events = testSubject.readEvents("test", aggregateIdentifier);
      long t = 30L;
      while (events.hasNext()) {
      DomainEventMessage event = events.next();
      assertEquals(t, event.getSequenceNumber());
      t++;
      }
      assertEquals(110L, t);
      }

      @Test
      @Transactional
      public void testLoadWithSnapshotEvent() {
      testSubject.appendEvents("test", aggregate1.getUncommittedEvents());
      aggregate1.commitEvents();
      entityManager.flush();
      entityManager.clear();
      testSubject.appendSnapshotEvent("test", aggregate1.createSnapshotEvent());
      entityManager.flush();
      entityManager.clear();
      aggregate1.changeState();
      testSubject.appendEvents("test", aggregate1.getUncommittedEvents());
      aggregate1.commitEvents();

      DomainEventStream actualEventStream = testSubject.readEvents("test", aggregate1.getIdentifier());
      List<DomainEventMessage> domainEvents = new ArrayList<DomainEventMessage>();
      while (actualEventStream.hasNext()) {
      DomainEventMessage next = actualEventStream.next();
      domainEvents.add(next);
      assertEquals(aggregate1.getIdentifier(), next.getAggregateIdentifier());
      }

      assertEquals(2, domainEvents.size());
      }

      @Test(expected = EventStreamNotFoundException.class)
      @Transactional
      public void testLoadNonExistent() {
      testSubject.readEvents("Stub", UUID.randomUUID());
      }

      @Test
      @Transactional
      public void testVisitAllEvents() {
      EventVisitor eventVisitor = mock(EventVisitor.class);
      testSubject.appendEvents("test", new SimpleDomainEventStream(createDomainEvents(77)));
      testSubject.appendEvents("test", new SimpleDomainEventStream(createDomainEvents(23)));

      testSubject.visitEvents(eventVisitor);
      verify(eventVisitor, times(100)).doWithEvent(isA(DomainEventMessage.class));
      }

      @Test
      @Transactional
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
      }

      @Test
      @Transactional
      public void testVisitEvents_AfterTimestamp() {
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
      testSubject.visitEvents(criteriaBuilder.property("timeStamp").greaterThan(onePM), eventVisitor);
      verify(eventVisitor, times(13 + 14)).doWithEvent(isA(DomainEventMessage.class));
      }

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
      }

      @DirtiesContext
      @Test
      @Transactional
      public void testPrunesSnaphotsWhenNumberOfSnapshotsExceedsConfiguredMaxSnapshotsArchived() {
      testSubject.setMaxSnapshotsArchived(1);

      StubAggregateRoot aggregate = new StubAggregateRoot();

      aggregate.changeState();
      testSubject.appendEvents("type", aggregate.getUncommittedEvents());
      aggregate.commitEvents();
      entityManager.flush();
      entityManager.clear();

      testSubject.appendSnapshotEvent("type", aggregate.createSnapshotEvent());
      entityManager.flush();
      entityManager.clear();

      aggregate.changeState();
      testSubject.appendEvents("type", aggregate.getUncommittedEvents());
      aggregate.commitEvents();
      entityManager.flush();
      entityManager.clear();

      testSubject.appendSnapshotEvent("type", aggregate.createSnapshotEvent());
      entityManager.flush();
      entityManager.clear();

      @SuppressWarnings({"unchecked"})
      List<SnapshotEventEntry> snapshots =
      entityManager.createQuery("SELECT e FROM SnapshotEventEntry e "
      + "WHERE e.type = 'type' "
      + "AND e.aggregateIdentifier = :aggregateIdentifier")
      .setParameter("aggregateIdentifier", aggregate.getIdentifier().toString())
      .getResultList();
      assertEquals("archived snapshot count", 1L, snapshots.size());
      assertEquals("archived snapshot sequence", 1L, snapshots.iterator().next().getSequenceNumber());
      }

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
      }

      private List<DomainEventMessage<StubStateChangedEvent>> createDomainEvents(int numberOfEvents) {
      List<DomainEventMessage<StubStateChangedEvent>> events = new ArrayList<DomainEventMessage<StubStateChangedEvent>>();
      final Object aggregateIdentifier = UUID.randomUUID();
      for (int t = 0; t < numberOfEvents; t++) {
      events.add(new GenericDomainEventMessage<StubStateChangedEvent>(
      aggregateIdentifier,
      t,
      new StubStateChangedEvent(), MetaData.emptyInstance()
      ));
      }
      return events;
      }





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
