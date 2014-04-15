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

namespace Governor\Framework\Test;

use Governor\Framework\EventSourcing\AggregateFactoryInterface;

/**
 * Description of FixtureTestGeneric
 *
 * @author david
 */
class FixtureTestGeneric extends \PHPUnit_Framework_TestCase
{

    private $fixture;
    private $mockAggregateFactory;

    public function setUp()
    {
        $this->fixture = Fixtures::newGivenWhenThenFixture(StandardAggregate::class);
        $this->fixture->setReportIllegalStateChange(false);
        $this->mockAggregateFactory = \Phake::mock(AggregateFactoryInterface::class);

        \Phake::when($this->mockAggregateFactory)->getAggregateType(\Phake::anyParameters())->thenReturn(StandardAggregate::class);
        \Phake::when($this->mockAggregateFactory)->getTypeIdentifier(\Phake::anyParameters())->thenReturn('StandardAggregate');
        \Phake::when($this->mockAggregateFactory)->createAggregate(\Phake::anyParameters())->thenReturn(new StandardAggregate("id1"));
    }

    public function testConfigureCustomAggregateFactory()
    {
        $this->fixture->registerAggregateFactory($this->mockAggregateFactory);
        $this->fixture->registerAnnotatedCommandHandler(new MyCommandHandler($this->fixture->getRepository(),
                $this->fixture->getEventBus()));

        $this->fixture->given(array(new MyEvent("id1", 1)))
                ->when(new TestCommand("id1"));

        \Phake::verify($this->mockAggregateFactory)->createAggregate(\Phake::equalTo("id1"),
                \Phake::anyParameters());
    }

    /**
     * expectedException \Governor\Framework\EventSourcing\IncompatibleAggregateException
     */
    public function testConfigurationOfRequiredCustomAggregateFactoryNotProvided_FailureOnGiven()
    {
        $this->fixture->given(array(new MyEvent("id1", 1)));
    }

    /**
     * expectedException \Governor\Framework\EventSourcing\IncompatibleAggregateException
     */
    public function testConfigurationOfRequiredCustomAggregateFactoryNotProvided_FailureOnGetRepository()
    {
        $this->fixture->getRepository();
    }

    public function testAggregateIdentifier_ServerGeneratedIdentifier()
    {
        $this->fixture->registerAggregateFactory($this->mockAggregateFactory);
        $this->fixture->registerAnnotatedCommandHandler(new MyCommandHandler($this->fixture->getRepository(),
                $this->fixture->getEventBus()));
        $this->fixture->givenNoPriorActivity()
                ->when(new CreateAggregateCommand());
    }

    /*
      @Test(expected = FixtureExecutionException.class)
      public void testInjectResources_CommandHandlerAlreadyRegistered() {
      fixture.registerAggregateFactory(mockAggregateFactory);
      fixture.registerAnnotatedCommandHandler(new MyCommandHandler(fixture.getRepository(), fixture.getEventBus()));
      fixture.registerInjectableResource("I am injectable");
      }

      @Test
      public void testAggregateIdentifier_IdentifierAutomaticallyDeducted() {
      fixture.registerAggregateFactory(mockAggregateFactory);
      fixture.registerAnnotatedCommandHandler(new MyCommandHandler(fixture.getRepository(), fixture.getEventBus()));
      fixture.given(new MyEvent("AggregateId", 1), new MyEvent("AggregateId", 2))
      .when(new TestCommand("AggregateId"))
      .expectEvents(new MyEvent("AggregateId", 3));

      DomainEventStream events = fixture.getEventStore().readEvents("StandardAggregate", "AggregateId");
      for (int t=0;t<3;t++) {
      assertTrue(events.hasNext());
      DomainEventMessage next = events.next();
      assertEquals("AggregateId", next.getAggregateIdentifier());
      assertEquals(t, next.getSequenceNumber());
      }
      }

      @Test
      public void testReadAggregate_WrongIdentifier() {
      fixture.registerAggregateFactory(mockAggregateFactory);
      fixture.registerAnnotatedCommandHandler(new MyCommandHandler(fixture.getRepository(), fixture.getEventBus()));
      TestExecutor exec = fixture.given(new MyEvent("AggregateId", 1));
      try {
      exec.when(new TestCommand("OtherIdentifier"));
      fail("Expected an AssertionError");
      } catch (AssertionError e) {
      assertTrue("Wrong message. Was: " + e.getMessage(), e.getMessage().contains("OtherIdentifier"));
      assertTrue("Wrong message. Was: " + e.getMessage(), e.getMessage().contains("AggregateId"));
      }
      }

      @Test(expected = EventStoreException.class)
      public void testFixtureGeneratesExceptionOnWrongEvents_DifferentAggregateIdentifiers() {
      fixture.getEventStore().appendEvents("whatever", new SimpleDomainEventStream(
      new GenericDomainEventMessage<StubDomainEvent>(UUID.randomUUID(), 0, new StubDomainEvent()),
      new GenericDomainEventMessage<StubDomainEvent>(UUID.randomUUID(), 0, new StubDomainEvent())));
      }

      @Test(expected = EventStoreException.class)
      public void testFixtureGeneratesExceptionOnWrongEvents_WrongSequence() {
      UUID identifier = UUID.randomUUID();
      fixture.getEventStore().appendEvents("whatever", new SimpleDomainEventStream(
      new GenericDomainEventMessage<StubDomainEvent>(identifier, 0, new StubDomainEvent()),
      new GenericDomainEventMessage<StubDomainEvent>(identifier, 2, new StubDomainEvent())));
      }

      private class StubDomainEvent {

      public StubDomainEvent() {
      }
      } */
}
