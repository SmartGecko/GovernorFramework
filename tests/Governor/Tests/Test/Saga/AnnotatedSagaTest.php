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

namespace Governor\Tests\Test\Saga;

use Hamcrest\Matchers as CoreMatchers;
use Governor\Framework\Domain\GenericEventMessage;
use Governor\Framework\Test\Matchers\Matchers;
use Governor\Framework\Test\Saga\AnnotatedSagaTestFixture;
use Ramsey\Uuid\Uuid;

/**
 * AnnotatedSaga unit tests
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
class AnnotatedSagaTest extends \PHPUnit_Framework_TestCase
{
    public function testFixtureApi_WhenEventOccurs()
    {
        $aggregate1 = Uuid::uuid1()->toString();
        $aggregate2 = Uuid::uuid1()->toString();

        $fixture = new AnnotatedSagaTestFixture(StubSaga::class);
        $validator = $fixture->givenAggregate($aggregate1)->published(
            [
                new GenericEventMessage(new TriggerSagaStartEvent($aggregate1)),
                new GenericEventMessage(new TriggerExistingSagaEvent($aggregate1))
            ]
        )->andThenAggregate($aggregate2)->published(
            [
                new GenericEventMessage(new TriggerSagaStartEvent($aggregate2))
            ]
        )->whenAggregate($aggregate1)->publishes(new GenericEventMessage(new TriggerSagaEndEvent($aggregate1)));

        $validator->expectActiveSagas(1);
        $validator->expectAssociationWith("identifier", $aggregate2);
        $validator->expectNoAssociationWith("identifier", $aggregate1);
        $validator->expectDispatchedCommandsEqualTo(array());
        $validator->expectNoDispatchedCommands();
        $validator->expectPublishedEventsMatching(Matchers::noEvents());


        /* TODO timers
        validator.expectScheduledEventOfType(Duration.standardMinutes(10), TimerTriggeredEvent.class);
        validator.expectScheduledEventMatching(Duration.standardMinutes(10), messageWithPayload(CoreMatchers.any(
        TimerTriggeredEvent.class)));
        validator.expectScheduledEvent(Duration.standardMinutes(10), new TimerTriggeredEvent(aggregate1.toString()));
        validator.expectScheduledEventOfType(fixture.currentTime().plusMinutes(10), TimerTriggeredEvent.class);
        validator.expectScheduledEventMatching(fixture.currentTime().plusMinutes(10),
        messageWithPayload(CoreMatchers.any(TimerTriggeredEvent.class)));
        validator.expectScheduledEvent(fixture.currentTime().plusMinutes(10),
        new TimerTriggeredEvent(aggregate1.toString()));
        */
    }


    public function testFixtureApi_AggregatePublishedEvent_NoHistoricActivity()
    {
        $fixture = new AnnotatedSagaTestFixture(StubSaga::class);

        $fixture->givenNoPriorActivity()
            ->whenAggregate("id")->publishes(new TriggerSagaStartEvent("id"))
            ->expectActiveSagas(1)
            ->expectAssociationWith("identifier", "id");
    }


    public function testFixtureApi_PublishedEvent_NoHistoricActivity()
    {
        $fixture = new AnnotatedSagaTestFixture(StubSaga::class);

        $fixture->givenNoPriorActivity()
            ->whenPublishingA(new GenericEventMessage(new TriggerSagaStartEvent("id")))
            ->expectActiveSagas(1)
            ->expectAssociationWith("identifier", "id");
    }


    public function testFixtureApi_WithApplicationEvents()
    {
        $aggregate1 = Uuid::uuid1()->toString();
        $aggregate2 = Uuid::uuid1()->toString();

        $fixture = new AnnotatedSagaTestFixture(StubSaga::class);

        $fixture->givenAPublished(new TimerTriggeredEvent(Uuid::uuid1()->toString()))
            ->andThenAPublished(new TimerTriggeredEvent(Uuid::uuid1()->toString()))
            ->whenPublishingA(new TimerTriggeredEvent(Uuid::uuid1()->toString()))
            ->expectActiveSagas(0)
            ->expectNoAssociationWith("identifier", $aggregate2)
            ->expectNoAssociationWith("identifier", $aggregate1)
            //->expectNoScheduledEvents()
            ->expectDispatchedCommandsEqualTo(array())
            ->expectPublishedEvents(array());
    }


    public function testFixtureApi_WhenEventIsPublishedToEventBus()
    {
        $aggregate1 = Uuid::uuid1()->toString();
        $aggregate2 = Uuid::uuid1()->toString();

        $fixture = new AnnotatedSagaTestFixture(StubSaga::class);
        $validator = $fixture
            ->givenAggregate($aggregate1)->published(
                array(
                    new TriggerSagaStartEvent($aggregate1),
                    new TriggerExistingSagaEvent($aggregate1)
                )
            )
            ->whenAggregate($aggregate1)->publishes(new TriggerExistingSagaEvent($aggregate1));

        $validator->expectActiveSagas(1);
        $validator->expectAssociationWith("identifier", $aggregate1);
        $validator->expectNoAssociationWith("identifier", $aggregate2);
        //validator.expectScheduledEventMatching(Duration.standardMinutes(10),
        // Matchers.messageWithPayload(CoreMatchers.any(Object.class)));
        $validator->expectDispatchedCommandsEqualTo(array());
        $validator->expectPublishedEventsMatching(
            Matchers::listWithAnyOf(
                array(
                    Matchers::messageWithPayload(CoreMatchers::any(SagaWasTriggeredEvent::class))
                )
            )
        );
    }

    /*
        public void testFixtureApi_ElapsedTimeBetweenEventsHasEffectOnScheduler() {
        UUID aggregate1 = UUID.randomUUID();
            AnnotatedSagaTestFixture fixture = new AnnotatedSagaTestFixture(StubSaga.class);
            FixtureExecutionResult validator = fixture
        // event schedules a TriggerEvent after 10 minutes from t0
        .givenAggregate(aggregate1).published(new TriggerSagaStartEvent(aggregate1.toString()))
        // time shifts to t0+5
        .andThenTimeElapses(Duration.standardMinutes(5))
        // reset event schedules a TriggerEvent after 10 minutes from t0+5
        .andThenAggregate(aggregate1).published(new ResetTriggerEvent(aggregate1.toString()))
        // when time shifts to t0+10
        .whenTimeElapses(Duration.standardMinutes(6));

            validator.expectActiveSagas(1);
            validator.expectAssociationWith("identifier", aggregate1);
            // 6 minutes have passed since the 10minute timer was reset,
            // so expect the timer to be scheduled for 4 minutes (t0 + 15)
            validator.expectScheduledEventMatching(Duration.standardMinutes(4),
                Matchers.messageWithPayload(CoreMatchers.any(Object.class)));
            validator.expectNoDispatchedCommands();
            validator.expectPublishedEvents();
        }


        @Test
        public void testFixtureApi_WhenTimeElapses_UsingMockGateway() throws Throwable {
        UUID identifier = UUID.randomUUID();
            UUID identifier2 = UUID.randomUUID();
            AnnotatedSagaTestFixture fixture = new AnnotatedSagaTestFixture(StubSaga.class);
            final StubGateway gateway = mock(StubGateway.class);
            fixture.registerCommandGateway(StubGateway.class, gateway);
            when(gateway.send(eq("Say hi!"))).thenReturn("Hi again!");

            fixture.givenAggregate(identifier).published(new TriggerSagaStartEvent(identifier.toString()))
            .andThenAggregate(identifier2).published(new TriggerExistingSagaEvent(identifier2.toString()))
            .whenTimeElapses(Duration.standardMinutes(35))
            .expectActiveSagas(1)
            .expectAssociationWith("identifier", identifier)
            .expectNoAssociationWith("identifier", identifier2)
            .expectNoScheduledEvents()
            .expectDispatchedCommandsEqualTo("Say hi!", "Hi again!")
            .expectPublishedEventsMatching(noEvents());

            verify(gateway).send("Say hi!");
            verify(gateway).send("Hi again!");
        }

        @Test
        public void testFixtureApi_WhenTimeElapses_UsingDefaults() throws Throwable {
        UUID identifier = UUID.randomUUID();
            UUID identifier2 = UUID.randomUUID();
            AnnotatedSagaTestFixture fixture = new AnnotatedSagaTestFixture(StubSaga.class);
            fixture.registerCommandGateway(StubGateway.class);

            fixture.givenAggregate(identifier).published(new TriggerSagaStartEvent(identifier.toString()))
            .andThenAggregate(identifier2).published(new TriggerExistingSagaEvent(identifier2.toString()))
            .whenTimeElapses(Duration.standardMinutes(35))
            .expectActiveSagas(1)
            .expectAssociationWith("identifier", identifier)
            .expectNoAssociationWith("identifier", identifier2)
            .expectNoScheduledEvents()
            // since we return null for the command, the other is never sent...
            .expectDispatchedCommandsEqualTo("Say hi!")
            .expectPublishedEventsMatching(noEvents());
        }

        @Test
        public void testFixtureApi_WhenTimeElapses_UsingCallbackBehavior() throws Throwable {
        UUID identifier = UUID.randomUUID();
            UUID identifier2 = UUID.randomUUID();
            AnnotatedSagaTestFixture fixture = new AnnotatedSagaTestFixture(StubSaga.class);
            CallbackBehavior commandHandler = mock(CallbackBehavior.class);
            when(commandHandler.handle(eq("Say hi!"), isA(MetaData.class))).thenReturn("Hi again!");
            fixture.setCallbackBehavior(commandHandler);
            fixture.registerCommandGateway(StubGateway.class);

            fixture.givenAggregate(identifier).published(new TriggerSagaStartEvent(identifier.toString()))
            .andThenAggregate(identifier2).published(new TriggerExistingSagaEvent(identifier2.toString()))
            .whenTimeElapses(Duration.standardMinutes(35))
            .expectActiveSagas(1)
            .expectAssociationWith("identifier", identifier)
            .expectNoAssociationWith("identifier", identifier2)
            .expectNoScheduledEvents()
            .expectDispatchedCommandsEqualTo("Say hi!", "Hi again!")
            .expectPublishedEventsMatching(noEvents());

            verify(commandHandler, times(2)).handle(isA(Object.class), eq(MetaData.emptyInstance()));
        }

        @Test
        public void testFixtureApi_WhenTimeAdvances() {
        UUID identifier = UUID.randomUUID();
            UUID identifier2 = UUID.randomUUID();
            AnnotatedSagaTestFixture fixture = new AnnotatedSagaTestFixture(StubSaga.class);
            fixture.registerCommandGateway(StubGateway.class);
            fixture.givenAggregate(identifier).published(new TriggerSagaStartEvent(identifier.toString()))
            .andThenAggregate(identifier2).published(new TriggerExistingSagaEvent(identifier2.toString()))

            .whenTimeAdvancesTo(new DateTime().plus(Duration.standardDays(1)))

            .expectActiveSagas(1)
            .expectAssociationWith("identifier", identifier)
            .expectNoAssociationWith("identifier", identifier2)
            .expectNoScheduledEvents()
            .expectDispatchedCommandsEqualTo("Say hi!");
        }*/
}