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

namespace Governor\Tests\EventStore\Filesystem;

use Ramsey\Uuid\Uuid;
use Governor\Framework\Domain\GenericDomainEventMessage;
use Governor\Framework\Domain\SimpleDomainEventStream;
use Governor\Framework\EventStore\EventStoreException;
use Governor\Framework\Serializer\JMSSerializer;
use Governor\Framework\Serializer\NullRevisionResolver;
use Governor\Tests\Stubs\StubDomainEvent;
use Governor\Framework\EventStore\Filesystem\SimpleEventFileResolver;
use Governor\Framework\EventStore\Filesystem\FilesystemEventStore;

class FilesystemEventStoreTest extends \PHPUnit_Framework_TestCase
{

    private $serializer;
    private $aggregateIdentifier;
    private $fileResolver;

    public function setUp()
    {
        $tempDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "governor";        
        @mkdir($tempDirectory);
        $this->fileResolver = new SimpleEventFileResolver($tempDirectory);
        $this->serializer = new JMSSerializer(new NullRevisionResolver());
        $this->aggregateIdentifier = Uuid::uuid1();
    }

    public function testSaveStreamAndReadBackIn()
    {
        $eventStore = new FilesystemEventStore($this->fileResolver,
                $this->serializer);

        $event1 = new GenericDomainEventMessage(
                $this->aggregateIdentifier, 0, new StubDomainEvent());
        $event2 = new GenericDomainEventMessage(
                $this->aggregateIdentifier, 1, new StubDomainEvent());
        $event3 = new GenericDomainEventMessage(
                $this->aggregateIdentifier, 2, new StubDomainEvent());
        $stream = new SimpleDomainEventStream(array($event1, $event2, $event3));
        $eventStore->appendEvents("test", $stream);

        $eventStream = $eventStore->readEvents("test",
                $this->aggregateIdentifier);
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

    /**
     * @expectedException \Governor\Framework\Repository\ConflictingModificationException
     */
    public function testShouldThrowExceptionUponDuplicateAggregateId()
    {
        $eventStore = new FileSystemEventStore($this->fileResolver,
                $this->serializer);

        $event1 = new GenericDomainEventMessage(
                $this->aggregateIdentifier, 0, new StubDomainEvent());
        $event2 = new GenericDomainEventMessage(
                $this->aggregateIdentifier, 1, new StubDomainEvent());
        $stream = new SimpleDomainEventStream(array($event1, $event2));
        $eventStore->appendEvents("test", $stream);

        $event3 = new GenericDomainEventMessage(
                $this->aggregateIdentifier, 0, new StubDomainEvent());
        $stream2 = new SimpleDomainEventStream(array($event3));
        $eventStore->appendEvents("test", $stream2);
    }

    public function testReadEventsWithIllegalSnapshot()
    {
      /*  $mockSerializer = $this->getMockBuilder('Governor\Framework\Serializer\JMSSerializer')
                ->setConstructorArgs(array(new NullRevisionResolver()))
                ->setMethods(array('serialize'))
                ->getMock();
        
        $eventStore = new FileSystemEventStore($this->fileResolver,
        $mockSerializer);

        $event1 = new GenericDomainEventMessage(
                $this->aggregateIdentifier, 0, new StubDomainEvent());
        $event2 = new GenericDomainEventMessage(
                $this->aggregateIdentifier, 1, new StubDomainEvent());

        $stream = new SimpleDomainEventStream(array($event1, $event2));
        $eventStore->appendEvents("test", $stream);
      
//doReturn(new SimpleSerializedObject<byte[]>("error".getBytes(), byte[].class, String.class.getName(), "old"))
//.when(serializer).serialize(anyObject(), eq(byte[].class));

        $eventStore->appendSnapshotEvent("test", $event2);

        $actual = $eventStore->readEvents("test", $this->aggregateIdentifier);
        $this->assertTrue($actual->hasNext());
        $this->assertEquals(0, $actual->next()->getScn());
        $this->assertEquals(1, $actual->next()->getScn());
        $this->assertFalse($actual->hasNext());*/
    }

    /*
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
     */

    public function testRead_FileNotReadable()
    {
        $mockEventFileResolver = $this->getMock('Governor\Framework\EventStore\Filesystem\EventFileResolverInterface');
        $mockFile = $this->getMockBuilder('\SplFileObject')
                ->setConstructorArgs(array(tempnam(sys_get_temp_dir(), 'governormockfile'),'ab+'))
                ->getMock();

        $mockEventFileResolver->expects($this->any())
                ->method('eventFileExists')
                ->will($this->returnValue(true));

        $mockEventFileResolver->expects($this->any())
                ->method('openEventFileForReading')
                ->will($this->returnValue($mockFile));

        $exception = new \Exception("Mock Exception");

        $mockFile->expects($this->any())
                ->method('fgetc')
                ->will($this->throwException($exception));

        $eventStore = new FileSystemEventStore($mockEventFileResolver, $this->serializer);

        try {
            $eventStore->readEvents("test", Uuid::uuid1()->toString());
            $this->fail("Expected an exception");
        } catch (EventStoreException $ex) {
            $this->assertSame($exception, $ex->getPrevious());
        }
    }

    /*
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
      } */

    public function testAppendSnapShot()
    {
        $eventStore = new FilesystemEventStore($this->fileResolver,
                $this->serializer);

        $counter = 0;

        $snapshot1 = new GenericDomainEventMessage(
                $this->aggregateIdentifier, 4, new StubDomainEvent());
        $snapshot2 = new GenericDomainEventMessage(
                $this->aggregateIdentifier, 9, new StubDomainEvent());
        $snapshot3 = new GenericDomainEventMessage(
                $this->aggregateIdentifier, 14, new StubDomainEvent());

        $this->writeEvents($counter, 5);
        $eventStore->appendSnapshotEvent("snapshotting", $snapshot1);
        $this->writeEvents($counter, 5);
        $eventStore->appendSnapshotEvent("snapshotting", $snapshot2);
        $this->writeEvents($counter, 5);
        $eventStore->appendSnapshotEvent("snapshotting", $snapshot3);
        $this->writeEvents($counter, 2);

        $eventStream = $eventStore->readEvents("snapshotting",
                $this->aggregateIdentifier);
        $actualEvents = array();
        while ($eventStream->hasNext()) {
            $actualEvents[] = $eventStream->next();
        }

        $this->assertEquals(14, $actualEvents[0]->getScn());
        $this->assertEquals(3, count($actualEvents));
    }

    private function writeEvents(&$counter, $numberOfEvents)
    {
        $eventStore = new FilesystemEventStore($this->fileResolver,
                $this->serializer);

        $events = array();
        for ($cc = 0; $cc < $numberOfEvents; $cc++) {
            $events[] = new GenericDomainEventMessage(
                    $this->aggregateIdentifier, $counter++,
                    new StubDomainEvent());
        }
        $eventStore->appendEvents("snapshotting",
                new SimpleDomainEventStream($events));
    }

    /*
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
