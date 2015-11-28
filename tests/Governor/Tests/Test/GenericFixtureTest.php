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

namespace Governor\Tests\Test;

use Governor\Framework\Domain\GenericDomainEventMessage;
use Governor\Framework\Domain\SimpleDomainEventStream;
use Governor\Framework\EventSourcing\AggregateFactoryInterface;
use Governor\Framework\Test\FixtureConfigurationInterface;
use Governor\Framework\Test\Fixtures;
use Ramsey\Uuid\Uuid;

/**
 * Class GenericFixtureTest.
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
class GenericFixtureTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var FixtureConfigurationInterface
     */
    private $fixture;

    /**
     * @var AggregateFactoryInterface
     */
    private $mockAggregateFactory;

    public function setUp()
    {
        $this->fixture = Fixtures::newGivenWhenThenFixture(StandardAggregate::class);
        $this->mockAggregateFactory = \Phake::mock(AggregateFactoryInterface::class);

        \Phake::when($this->mockAggregateFactory)->getAggregateType(\Phake::anyParameters())->thenReturn(
            StandardAggregate::class
        );
        \Phake::when($this->mockAggregateFactory)->getTypeIdentifier(\Phake::anyParameters())->thenReturn(
            'StandardAggregate'
        );
        \Phake::when($this->mockAggregateFactory)->createAggregate(\Phake::anyParameters())->thenReturn(
            new StandardAggregate("id1")
        );
    }

    public function testConfigureCustomAggregateFactory()
    {
        $this->fixture->registerAggregateFactory($this->mockAggregateFactory);
        $this->fixture->registerAnnotatedCommandHandler(
            new MyCommandHandler(
                $this->fixture->getRepository(),
                $this->fixture->getEventBus()
            )
        );

        $this->fixture->given(
            [
                new MyEvent("id1", 1)
            ]
        )
            ->when(new TestCommand("id1"));

        \Phake::verify($this->mockAggregateFactory, \Phake::atLeast(1))->createAggregate(
            \Phake::equalTo("id1"),
            \Phake::anyParameters()
        );
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


    /**
     * @expectedException \Governor\Framework\Test\FixtureExecutionException
     */
    public function testInjectResources_CommandHandlerAlreadyRegistered()
    {
        $this->fixture->registerAggregateFactory($this->mockAggregateFactory);
        $this->fixture->registerAnnotatedCommandHandler(
            new MyCommandHandler($this->fixture->getRepository(), $this->fixture->getEventBus())
        );

        $this->fixture->registerInjectableResource('id', new \stdClass());
    }


    public function testAggregateIdentifier_IdentifierAutomaticallyDeducted()
    {
        $this->fixture->registerAggregateFactory($this->mockAggregateFactory);
        $this->fixture->registerAnnotatedCommandHandler(
            new MyCommandHandler($this->fixture->getRepository(), $this->fixture->getEventBus())
        );
        $this->fixture->given(array(new MyEvent("AggregateId", 1), new MyEvent("AggregateId", 2)))
            ->when(new TestCommand("AggregateId"))
            ->expectEvents(array(new MyEvent("AggregateId", 3)));


        $events = $this->fixture->getEventStore()->readEvents("StandardAggregate", "AggregateId");

        for ($t = 0; $t < 3; $t++) {
            $this->assertTrue($events->hasNext());
            $next = $events->next();

            $this->assertEquals("AggregateId", $next->getAggregateIdentifier());
            $this->assertEquals($t, $next->getScn());
        }
    }

    public function testReadAggregate_WrongIdentifier()
    {
        $this->fixture->registerAggregateFactory($this->mockAggregateFactory);
        $this->fixture->registerAnnotatedCommandHandler(
            new MyCommandHandler(
                $this->fixture->getRepository(),
                $this->fixture->getEventBus()
            )
        );
        $exec = $this->fixture->given(array(new MyEvent("AggregateId", 1)));
        try {
            $exec->when(new TestCommand("OtherIdentifier"));
            $this->fail("Expected an AssertionError");
        } catch (\Exception $ex) {
            echo $ex->getMessage();

            /*  $this->assertTrue(
                  "Wrong message. Was: " . $ex->getMessage(),
                  $ex->getMessage()
              ); // . contains("OtherIdentifier"));
              $this->assertTrue(
                  "Wrong message. Was: " . $ex->getMessage(),
                  $ex->getMessage()
              ); // . contains("AggregateId"));*/
        }
    }


    /**
     * @expectedException \Governor\Framework\EventStore\EventStoreException
     */
    public function testFixtureGeneratesExceptionOnWrongEvents_DifferentAggregateIdentifiers()
    {
        $this->fixture->getEventStore()->appendEvents(
            "whatever",
            new SimpleDomainEventStream(
                [
                    new GenericDomainEventMessage(Uuid::uuid1()->toString(), 0, new StubDomainEvent()),
                    new GenericDomainEventMessage(Uuid::uuid1()->toString(), 0, new StubDomainEvent())
                ]
            )
        );
    }


    /**
     * @expectedException \Governor\Framework\EventStore\EventStoreException
     */
    public function testFixtureGeneratesExceptionOnWrongEvents_WrongSequence()
    {
        $identifier = Uuid::uuid1()->toString();

        $this->fixture->getEventStore()->appendEvents(
            "whatever",
            new SimpleDomainEventStream(
                [
                    new GenericDomainEventMessage($identifier, 0, new StubDomainEvent()),
                    new GenericDomainEventMessage($identifier, 2, new StubDomainEvent())
                ]
            )
        );
    }

}

class StubDomainEvent
{

}