<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\EventStore\Filesystem;

use Rhumsaa\Uuid\Uuid;
use Governor\Framework\Domain\GenericDomainEventMessage;
use Governor\Framework\Domain\SimpleDomainEventStream;
use Governor\Framework\Serializer\JMSSerializer;
use Governor\Framework\Stubs\StubDomainEvent;

class FilesystemEventStoreTest extends \PHPUnit_Framework_TestCase
{

    private $tempDirectory;
    private $serializer;
    private $aggregateIdentifier;

    public function setUp()
    {
        $this->tempDirectory = sys_get_temp_dir();
        $this->serializer = new JMSSerializer();
        $this->aggregateIdentifier = Uuid::uuid1();
    }

    public function testSaveStreamAndReadBackIn()
    {
        $eventStore = new FilesystemEventStore(new SimpleEventFileResolver($this->tempDirectory), $this->serializer);

        $event1 = new GenericDomainEventMessage(
                $this->aggregateIdentifier, 0, new StubDomainEvent());
        $event2 = new GenericDomainEventMessage(
                $this->aggregateIdentifier, 1, new StubDomainEvent());
        $event3 = new GenericDomainEventMessage(
                $this->aggregateIdentifier, 2, new StubDomainEvent());
        $stream = new SimpleDomainEventStream(array($event1, $event2, $event3));
        $eventStore->appendEvents("test", $stream);

        $eventStream = $eventStore->readEvents("test", $this->aggregateIdentifier);
        $domainEvents = array();

        while ($eventStream->hasNext()) {
            $domainEvents[] = $eventStream->next();
        }

        $this->assertEquals($event1->getIdentifier(),
                $domainEvents[0]->getIdentifier());
        $this->assertEquals($event2->getIdentifier(),
                $domainEvents[1]->getIdentifier());
        $this->assertEquals($event3->getIdentifier(),
                $domainEvents[2]->getIdentifier());
    }

    /*
      @Test(expected = ConflictingModificationException.class)
      // Issue AXON-121: FileSystemEventStore allows duplicate construction of the same AggregateRoot
      public void testShouldThrowExceptionUponDuplicateAggregateId() {
      FileSystemEventStore eventStore = new FileSystemEventStore(new SimpleEventFileResolver(eventFileBaseDir));

      GenericDomainEventMessage<StubDomainEvent> event1 = new GenericDomainEventMessage<StubDomainEvent>(
      aggregateIdentifier,
      0,
      new StubDomainEvent());
      GenericDomainEventMessage<StubDomainEvent> event2 = new GenericDomainEventMessage<StubDomainEvent>(
      aggregateIdentifier,
      1,
      new StubDomainEvent());
      DomainEventStream stream = new SimpleDomainEventStream(event1, event2);
      eventStore.appendEvents("test", stream);

      GenericDomainEventMessage<StubDomainEvent> event3 = new GenericDomainEventMessage<StubDomainEvent>(
      aggregateIdentifier,
      0,
      new StubDomainEvent());
      DomainEventStream stream2 = new SimpleDomainEventStream(event3);
      eventStore.appendEvents("test", stream2);
      }

      @Test
      public void testReadEventsWithIllegalSnapshot() {
      final XStreamSerializer serializer = spy(new XStreamSerializer());
      FileSystemEventStore eventStore = new FileSystemEventStore(serializer,
      new SimpleEventFileResolver(eventFileBaseDir));

      GenericDomainEventMessage<StubDomainEvent> event1 = new GenericDomainEventMessage<StubDomainEvent>(
      aggregateIdentifier,
      0,
      new StubDomainEvent());
      GenericDomainEventMessage<StubDomainEvent> event2 = new GenericDomainEventMessage<StubDomainEvent>(
      aggregateIdentifier,
      1,
      new StubDomainEvent());
      DomainEventStream stream = new SimpleDomainEventStream(event1, event2);
      eventStore.appendEvents("test", stream);

      doReturn(new SimpleSerializedObject<byte[]>("error".getBytes(), byte[].class, String.class.getName(), "old"))
      .when(serializer).serialize(anyObject(), eq(byte[].class));
      eventStore.appendSnapshotEvent("test", event2);

      DomainEventStream actual = eventStore.readEvents("test", aggregateIdentifier);
      assertTrue(actual.hasNext());
      assertEquals(0, actual.next().getSequenceNumber());
      assertEquals(1, actual.next().getSequenceNumber());
      assertFalse(actual.hasNext());
      }

      @Test
      // Issue #25: XStreamFileSystemEventStore fails when event data contains newline character
      public void testSaveStreamAndReadBackIn_NewLineInEvent() {
      FileSystemEventStore eventStore = new FileSystemEventStore(new SimpleEventFileResolver(eventFileBaseDir));

      String description = "This is a description with a \n newline character and weird chars éçè\u6324.";
      StringBuilder stringBuilder = new StringBuilder(description);
      for (int i = 0; i < 100; i++) {
      stringBuilder.append(
      "Some more text to make this event really long. It should not be a problem for the event serializer.");
      }
      description = stringBuilder.toString();
      GenericDomainEventMessage<MyStubDomainEvent> event1 = new GenericDomainEventMessage<MyStubDomainEvent>(
      aggregateIdentifier,
      0,
      new MyStubDomainEvent(description));
      GenericDomainEventMessage<StubDomainEvent> event2 = new GenericDomainEventMessage<StubDomainEvent>(
      aggregateIdentifier,
      1,
      new StubDomainEvent());

      DomainEventStream stream = new SimpleDomainEventStream(event1, event2);
      eventStore.appendEvents("test", stream);

      DomainEventStream eventStream = eventStore.readEvents("test", aggregateIdentifier);
      List<DomainEventMessage<? extends Object>> domainEvents = new ArrayList<DomainEventMessage<? extends Object>>();
      while (eventStream.hasNext()) {
      domainEvents.add(eventStream.next());
      }
      MyStubDomainEvent actualEvent1 = (MyStubDomainEvent) domainEvents.get(0).getPayload();
      assertEquals(description, actualEvent1.getDescription());
      assertEquals(event2.getPayloadType(), domainEvents.get(1).getPayloadType());
      assertEquals(event2.getIdentifier(), domainEvents.get(1).getIdentifier());
      }

      @Test
      public void testRead_FileNotReadable() throws IOException {
      EventFileResolver mockEventFileResolver = mock(EventFileResolver.class);
      InputStream mockInputStream = mock(InputStream.class);
      when(mockEventFileResolver.eventFileExists(isA(String.class), any())).thenReturn(true);
      when(mockEventFileResolver.openEventFileForReading(isA(String.class), any()))
      .thenReturn(mockInputStream);
      IOException exception = new IOException("Mock Exception");
      when(mockInputStream.read()).thenThrow(exception);
      when(mockInputStream.read(Matchers.<byte[]>any())).thenThrow(exception);
      when(mockInputStream.read(Matchers.<byte[]>any(), anyInt(), anyInt())).thenThrow(exception);
      FileSystemEventStore eventStore = new FileSystemEventStore(mockEventFileResolver);

      try {
      eventStore.readEvents("test", UUID.randomUUID());
      fail("Expected an exception");
      } catch (EventStoreException e) {
      assertSame(exception, e.getCause());
      }
      }

      @Test
      public void testWrite_FileDoesNotExist() throws IOException {
      Object aggregateIdentifier = "aggregateIdentifier";
      IOException exception = new IOException("Mock");
      EventFileResolver mockEventFileResolver = mock(EventFileResolver.class);
      when(mockEventFileResolver.openEventFileForWriting(isA(String.class), isA(Object.class)))
      .thenThrow(exception);
      FileSystemEventStore eventStore = new FileSystemEventStore(mockEventFileResolver);

      GenericDomainEventMessage<StubDomainEvent> event1 = new GenericDomainEventMessage<StubDomainEvent>(
      aggregateIdentifier,
      0,
      new StubDomainEvent());
      GenericDomainEventMessage<StubDomainEvent> event2 = new GenericDomainEventMessage<StubDomainEvent>(
      aggregateIdentifier,
      1,
      new StubDomainEvent());
      GenericDomainEventMessage<StubDomainEvent> event3 = new GenericDomainEventMessage<StubDomainEvent>(
      aggregateIdentifier,
      2,
      new StubDomainEvent());
      DomainEventStream stream = new SimpleDomainEventStream(event1, event2, event3);

      try {
      eventStore.appendEvents("test", stream);
      fail("Expected an exception");
      } catch (EventStoreException e) {
      assertEquals(exception, e.getCause());
      }
      }

      @Test
      public void testAppendSnapShot() {
      FileSystemEventStore eventStore = new FileSystemEventStore(new SimpleEventFileResolver(eventFileBaseDir));

      AtomicInteger counter = new AtomicInteger(0);

      GenericDomainEventMessage<StubDomainEvent> snapshot1 = new GenericDomainEventMessage<StubDomainEvent>(
      aggregateIdentifier,
      4,
      new StubDomainEvent());
      GenericDomainEventMessage<StubDomainEvent> snapshot2 = new GenericDomainEventMessage<StubDomainEvent>(
      aggregateIdentifier,
      9,
      new StubDomainEvent());
      GenericDomainEventMessage<StubDomainEvent> snapshot3 = new GenericDomainEventMessage<StubDomainEvent>(
      aggregateIdentifier,
      14,
      new StubDomainEvent());

      writeEvents(counter, 5);
      eventStore.appendSnapshotEvent("snapshotting", snapshot1);
      writeEvents(counter, 5);
      eventStore.appendSnapshotEvent("snapshotting", snapshot2);
      writeEvents(counter, 5);
      eventStore.appendSnapshotEvent("snapshotting", snapshot3);
      writeEvents(counter, 2);

      DomainEventStream eventStream = eventStore.readEvents("snapshotting", aggregateIdentifier);
      List<DomainEventMessage<? extends Object>> actualEvents = new ArrayList<DomainEventMessage<? extends Object>>();
      while (eventStream.hasNext()) {
      actualEvents.add(eventStream.next());
      }
      assertEquals(14L, actualEvents.get(0).getSequenceNumber());
      assertEquals(3, actualEvents.size());
      }

      private void writeEvents(AtomicInteger counter, int numberOfEvents) {
      FileSystemEventStore eventStore = new FileSystemEventStore(new SimpleEventFileResolver(eventFileBaseDir));

      List<DomainEventMessage> events = new ArrayList<DomainEventMessage>();
      for (int t = 0; t < numberOfEvents; t++) {
      GenericDomainEventMessage<StubDomainEvent> event = new GenericDomainEventMessage<StubDomainEvent>(
      aggregateIdentifier,
      counter.getAndIncrement(),
      new StubDomainEvent());
      events.add(event);
      }
      eventStore.appendEvents("snapshotting", new SimpleDomainEventStream(events));
      }

      public static class MyStubDomainEvent extends StubDomainEvent {

      private static final long serialVersionUID = -7959231436742664073L;
      private final String description;

      public MyStubDomainEvent(String description) {
      this.description = description;
      }

      public String getDescription() {
      return description;
      }
      } */
}
