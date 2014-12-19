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

namespace Governor\Framework\Test\Saga;

use Governor\Framework\CommandHandling\CommandBusInterface;
use Governor\Framework\CommandHandling\CommandHandlerInterface;
use Governor\Framework\EventHandling\EventBusInterface;
use Governor\Framework\EventSourcing\AggregateFactoryInterface;
use Governor\Framework\EventSourcing\EventSourcingRepository;
use Governor\Framework\EventStore\EventStoreInterface;
use Governor\Framework\Repository\RepositoryInterface;
use Governor\Framework\Saga\Annotation\AnnotatedSagaManager;
use Governor\Framework\Test\FixtureConfigurationInterface;
use Governor\Framework\Test\TestExecutorInterface;
use Governor\Framework\Test\Utils\RecordingCommandBus;
use Psr\Log\LoggerInterface;

/**
 * Description of GivenWhenThenTestFixture
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
class AnnotatedSagaTestFixture implements FixtureConfigurationInterface, ContinuedGivenStateInterface
{

    private $eventScheduler; //StubEventScheduler
    /**
     * @var AnnotatedSagaManager
     */
    private $sagaManager;
    /**
     * @var array
     */
    private $registeredResources = array();

    private $aggregatePublishers = array(); //new HashMap<Object, AggregateEventPublisherImpl>();
    //private FixtureExecutionResultImpl fixtureExecutionResult;
    /**
     * @var RecordingCommandBus
     */
    private $commandBus;



    /**
     * Use this method to indicate that an aggregate with given identifier published certain events.
     * <p/>
     * Can be chained to build natural sentences:<br/> <code>andThenAggregate(someIdentifier).published(someEvents)
     * </code>
     *
     * @param string $aggregateIdentifier The identifier of the aggregate the events should appear to come from
     * @return GivenAggregateEventPublisherInterface an object that allows registration of the actual events to send
     */
    public function andThenAggregate($aggregateIdentifier)
    {
        // TODO: Implement andThenAggregate() method.
    }

    /**
     * Simulate time shifts in the current given state. This can be useful when the time between given events is of
     * importance.
     *
     * @param \DateInterval $elapsedTime The amount of time that will elapse
     * @return ContinuedGivenState an object that allows registration of the actual events to send
     */
    public function andThenTimeElapses(\DateInterval $elapsedTime)
    {
        // TODO: Implement andThenTimeElapses() method.
    }

    /**
     * Simulate time shifts in the current given state. This can be useful when the time between given events is of
     * importance.
     *
     * @param \DateTime $newDateTime The time to advance the clock to
     * @return ContinuedGivenState an object that allows registration of the actual events to send
     */
    public function andThenTimeAdvancesTo(\DateTime $newDateTime)
    {
        // TODO: Implement andThenTimeAdvancesTo() method.
    }

    /**
     * Indicates that the given <code>event</code> has been published in the past. This event is sent to the associated
     * sagas.
     *
     * @param mixed $event The event to publish
     * @return ContinuedGivenState an object that allows chaining of more given state
     */
    public function  andThenAPublished($event)
    {
        // TODO: Implement andThenAPublished() method.
    }

    /**
     * Registers an arbitrary event sourcing <code>repository</code> with the fixture. The repository must be wired
     * with the Event Store of this test fixture.
     * <p/>
     * Should not be used in combination with registerAggregateFactory(), as that will overwrite any repository previously registered.
     *
     * @param EventSourcingRepository $repository The repository to use in the test case
     * @return FixtureConfigurationInterface the current FixtureConfiguration, for fluent interfacing
     */
    public function registerRepository(EventSourcingRepository $repository)
    {
        // TODO: Implement registerRepository() method.
    }

    /**
     * Registers the given <code>aggregateFactory</code> with the fixture. The repository used by the fixture will use
     * the given factory to create new aggregate instances. Defaults to an Aggregate Factory that uses the no-arg
     * constructor to create new instances.
     * <p/>
     * Should not be used in combination with registerRepository(), as that will overwrite any aggregate factory previously registered.
     *
     * @param AggregateFactoryInterface $aggregateFactory The Aggregate Factory to create empty aggregates with
     * @return FixtureConfigurationInterface the current FixtureConfiguration, for fluent interfacing
     */
    public function registerAggregateFactory(AggregateFactoryInterface $aggregateFactory)
    {
        // TODO: Implement registerAggregateFactory() method.
    }

    /**
     * Registers an <code>annotatedCommandHandler</code> with this fixture. This will register this command handler
     * with the command bus used in this fixture.
     *
     * @param mixed $annotatedCommandHandler The command handler to register for this test
     * @return FixtureConfigurationInterface the current FixtureConfiguration, for fluent interfacing
     */
    public function registerAnnotatedCommandHandler($annotatedCommandHandler)
    {
        // TODO: Implement registerAnnotatedCommandHandler() method.
    }

    /**
     * Registers a <code>commandHandler</code> to handle commands of the given <code>commandType</code> with the
     * command bus used by this fixture.
     *
     * @param string $commandName The name of the command to register the handler for
     * @param CommandHandlerInterface $commandHandler The handler to register
     * @return FixtureConfigurationInterface the current FixtureConfiguration, for fluent interfacing
     */
    public function registerCommandHandler(
        $commandName,
        CommandHandlerInterface $commandHandler
    ) {
        // TODO: Implement registerCommandHandler() method.
    }

    /**
     * Registers a resource that is eligible for injection in handler method.
     * These resource must be registered <em>before</em> registering any command handler.
     *
     * @param string $id The resource id.
     * @param mixed $resource The resource eligible for injection
     * @return FixtureConfigurationInterface the current FixtureConfiguration, for fluent interfacing
     */
    public function registerInjectableResource($id, $resource)
    {
        // TODO: Implement registerInjectableResource() method.
    }

    /**
     * Configures the given <code>domainEvents</code> as the "given" events. These are the events returned by the event
     * store when an aggregate is loaded.
     * <p/>
     * If an item in the given <code>domainEvents</code> implements {@link org.axonframework.domain.Message}, the
     * payload and meta data from that message are copied into a newly created Domain Event Message. Otherwise, a
     * Domain Event Message with the item as payload and empty meta data is created.
     *
     * @param array $domainEvents the domain events the event store should return
     * @return TestExecutorInterface TestExecutor instance that can execute the test with this configuration
     */
    public function given(array $domainEvents)
    {
        // TODO: Implement given() method.
    }

    /**
     * Indicates that no relevant activity has occurred in the past. The behavior of this method is identical to giving
     * no events in the {@link #given(java.util.List)} method.
     *
     * @return TestExecutorInterface a TestExecutor instance that can execute the test with this configuration
     */
    public function givenNoPriorActivity()
    {
        // TODO: Implement givenNoPriorActivity() method.
    }

    /**
     * Configures the given <code>commands</code> as the command that will provide the "given" events. The commands are
     * executed, and the resulting stored events are captured.
     *
     * @param array $commands the domain events the event store should return
     * @return TestExecutorInterface a TestExecutor instance that can execute the test with this configuration
     */
    public function givenCommands(array $commands)
    {
        // TODO: Implement givenCommands() method.
    }

    /**
     * Returns the command bus used by this fixture. The command bus is provided for wiring purposes only, for example
     * to support composite commands (a single command that causes the execution of one or more others).
     *
     * @return CommandBusInterface the command bus used by this fixture
     */
    public function getCommandBus()
    {
        // TODO: Implement getCommandBus() method.
    }

    /**
     * Returns the event bus used by this fixture. The event bus is provided for wiring purposes only, for example to
     * allow command handlers to publish events other than Domain Events. Events published on the returned event bus
     * are recorded an evaluated in the {@link ResultValidator} operations.
     *
     * @return EventBusInterface the event bus used by this fixture
     */
    public function getEventBus()
    {
        // TODO: Implement getEventBus() method.
    }

    /**
     * Returns the event store used by this fixture. This event store is provided for wiring purposes only.
     *
     * @return EventStoreInterface the event store used by this fixture
     */
    public function getEventStore()
    {
        // TODO: Implement getEventStore() method.
    }

    /**
     * Returns the repository used by this fixture. This repository is provided for wiring purposes only. The
     * repository is configured to use the fixture's event store to load events.
     *
     * @return RepositoryInterface the repository used by this fixture
     */
    public function getRepository()
    {
        // TODO: Implement getRepository() method.
    }

    /**
     * Sets whether or not the fixture should detect and report state changes that occur outside of Event Handler
     * methods.
     *
     * @param boolean $reportIllegalStateChange whether or not to detect and report state changes outside of Event Handler
     *                                 methods.
     */
    public function setReportIllegalStateChange($reportIllegalStateChange)
    {
        // TODO: Implement setReportIllegalStateChange() method.
    }

    /**
     * Returns the logger associated with this fixture.
     *
     * @return LoggerInterface
     */
    public function getLogger()
    {
        // TODO: Implement getLogger() method.
    }

    /**
     * Use this method to indicate that an aggregate with given identifier should publish certain events, <em>while
     * recording the outcome</em>. In contrast to the {@link FixtureConfiguration#givenAggregate(Object)} given} and
     * {@link org.axonframework.test.saga.ContinuedGivenState#andThenAggregate(Object)} andThen} methods, this method
     * will start recording activity on the EventBus and CommandBus.
     * <p/>
     * Can be chained to build natural sentences:<br/> <code>whenAggregate(someIdentifier).publishes(anEvent)</code>
     * <p/>
     * Note that if you inject resources using {@link FixtureConfiguration#registerResource(Object)}, you may need to
     * reset them yourself if they are manipulated by the Saga in the "given" stage of the test.
     *
     * @param mixed $aggregateIdentifier The identifier of the aggregate the events should appear to come from
     * @return WhenAggregateEventPublisher an object that allows registration of the actual events to send
     */
    public function whenAggregate($aggregateIdentifier)
    {
        // TODO: Implement whenAggregate() method.
    }

    /**
     * Use this method to indicate an application is published, <em>while recording the outcome</em>.
     * <p/>
     * Note that if you inject resources using {@link FixtureConfiguration#registerResource(Object)}, you may need to
     * reset them yourself if they are manipulated by the Saga in the "given" stage of the test.
     *
     * @param mixed $event the event to publish
     * @return FixtureExecutionResultInterface an object allowing you to verify the test results
     */
    public function whenPublishingA($event)
    {
        // TODO: Implement whenPublishingA() method.
    }

    /**
     * Mimic an elapsed time with no relevant activity for the Saga. If any Events are scheduled to be published within
     * this time frame, they are published. All activity by the Saga on the CommandBus and EventBus (meaning that
     * scheduled events are excluded) is recorded.
     * <p/>
     * Note that if you inject resources using {@link FixtureConfiguration#registerResource(Object)}, you may need to
     * reset them yourself if they are manipulated by the Saga in the "given" stage of the test.
     *
     * @param \DateInterval $elapsedTime The amount of time to elapse
     * @return FixtureExecutionResultInterface an object allowing you to verify the test results
     */
    public function whenTimeElapses(\DateInterval $elapsedTime)
    {
        // TODO: Implement whenTimeElapses() method.
    }

    /**
     * Mimic an elapsed time with no relevant activity for the Saga. If any Events are scheduled to be published within
     * this time frame, they are published. All activity by the Saga on the CommandBus and EventBus (meaning that
     * scheduled events are excluded) is recorded.
     * <p/>
     * Note that if you inject resources using {@link FixtureConfiguration#registerResource(Object)}, you may need to
     * reset them yourself if they are manipulated by the Saga in the "given" stage of the test.
     *
     * @param \DateTime $newDateTime The time to advance the clock to
     * @return FixtureExecutionResultInterface an object allowing you to verify the test results
     */
    public function whenTimeAdvancesTo(\DateTime $newDateTime)
    {
        // TODO: Implement whenTimeAdvancesTo() method.
    }

}