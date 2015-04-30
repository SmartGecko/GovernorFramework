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

use Governor\Framework\CommandHandling\Gateway\DefaultCommandGateway;
use Governor\Framework\CommandHandling\Gateway\CommandGatewayInterface;
use Governor\Framework\EventHandling\InMemoryEventListenerRegistry;
use Governor\Framework\Test\FixtureParameterResolverFactory;
use Governor\Framework\Domain\GenericEventMessage;
use Governor\Framework\EventHandling\SimpleEventBus;
use Governor\Framework\Saga\Annotation\AnnotatedSagaManager;
use Governor\Framework\Saga\GenericSagaFactory;
use Governor\Framework\Saga\Repository\Memory\InMemorySagaRepository;
use Governor\Framework\Test\Utils\RecordingCommandBus;

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
    public $sagaManager;
    /**
     * @var array
     */
    private $registeredResources = array();

    /**
     * @var array
     */
    private $aggregatePublishers = array(); //new HashMap<Object, AggregateEventPublisherImpl>();

    /**
     * @var FixtureExecutionResultInterface
     */
    public $fixtureExecutionResult;
    /**
     * @var RecordingCommandBus
     */
    private $commandBus;

    /**
     * @var FixtureParameterResolverFactory
     */
    private $fixtureParameterResolverFactory;

    public function __construct($sagaType)
    {
        //eventScheduler = new StubEventScheduler();
        $eventScheduler = null;
        $genericSagaFactory = new GenericSagaFactory(new FixtureResourceInjector($this->registeredResources));

        $eventBus = new SimpleEventBus(new InMemoryEventListenerRegistry());
        $sagaRepository = new InMemorySagaRepository();
        $this->sagaManager = new AnnotatedSagaManager($sagaRepository, $genericSagaFactory, array($sagaType));
        $this->sagaManager->setSuppressExceptions(false);

        $this->commandBus = new RecordingCommandBus();

        $this->registeredResources['governor.event_bus.default'] = $eventBus;
        $this->registeredResources['governor.command_bus.default'] = $this->commandBus;
        $this->registeredResources['governor.command_gateway.default'] = new DefaultCommandGateway($this->commandBus);

        //registeredResources.add(eventScheduler);

        $this->fixtureExecutionResult = new FixtureExecutionResultImpl(
            $sagaRepository, $eventScheduler, $eventBus, $this->commandBus,
            $sagaType
        );

        $this->fixtureParameterResolverFactory = new FixtureParameterResolverFactory();

        foreach ($this->registeredResources as $id => $resource) {
            $this->fixtureParameterResolverFactory->registerService($id, $resource);
        }
    }

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
        return $this->givenAggregate($aggregateIdentifier);
    }

    /**
     * Simulate time shifts in the current given state. This can be useful when the time between given events is of
     * importance.
     *
     * @param \DateInterval $elapsedTime The amount of time that will elapse
     * @return ContinuedGivenStateInterface an object that allows registration of the actual events to send
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
     * @return ContinuedGivenStateInterface an object that allows registration of the actual events to send
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
     * @return ContinuedGivenStateInterface an object that allows chaining of more given state
     */
    public function  andThenAPublished($event)
    {
        $this->sagaManager->handle(GenericEventMessage::asEventMessage($event));

        return $this;
    }

    /**
     * Registers the given <code>resource</code>. When a Saga is created, all resources are injected on that instance
     * before any Events are passed onto it.
     * <p/>
     * Note that a CommandBus, EventBus and EventScheduler are already registered as resources, and need not be
     * registered again.
     * <p/>
     * Also note that you might need to reset the resources manually if you want to isolate behavior during the "when"
     * stage of the test.
     *
     * @param string $id resource identifier
     * @param mixed $resource the resource to register.
     */
    public function registerResource($id, $resource)
    {
        $this->registeredResources[$id] = $resource;
        $this->fixtureParameterResolverFactory->registerService($id, $resource);
    }

    /**
     * Creates a Command Gateway for the given <code>gatewayInterface</code> and registers that as a resource. The
     * gateway will dispatch commands on the Command Bus contained in this Fixture, so that you can validate commands
     * using {@link FixtureExecutionResult#expectDispatchedCommandsEqualTo(Object...)} and {@link
     * FixtureExecutionResult#expectDispatchedCommandsMatching(org.hamcrest.Matcher)}.
     * <p/>
     * Note that you need to use {@link #setCallbackBehavior(org.axonframework.test.utils.CallbackBehavior)} to defined
     * the behavior of commands when expecting return values. Alternatively, you can use {@link
     * #registerCommandGateway(Class, Object)} to define behavior using a stub implementation.
     *
     * @param CommandGatewayInterface $gatewayInterface The interface describing the gateway
     * @return CommandGatewayInterface the gateway implementation being registered as a resource.
     */
    public function registerCommandGateway(CommandGatewayInterface $gatewayInterface)
    {
        // TODO: Implement registerCommandGateway() method.
    }

    /**
     * Use this method to indicate that an aggregate with given identifier published certain events.
     * <p/>
     * Can be chained to build natural sentences:<br/>
     * <code>andThenAggregate(someIdentifier).published(someEvents)</code>
     *
     * @param string $aggregateIdentifier The identifier of the aggregate the events should appear to come from
     * @return GivenAggregateEventPublisherInterface an object that allows registration of the actual events to send
     */
    public function givenAggregate($aggregateIdentifier)
    {
        return $this->getPublisherFor($aggregateIdentifier);
    }

    /**
     * Indicates that the given <code>applicationEvent</code> has been published in the past. This event is sent to the
     * associated sagas.
     *
     * @param mixed $event The event to publish
     * @return ContinuedGivenStateInterface an object that allows chaining of more given state
     */
    public function givenAPublished($event)
    {
        $this->sagaManager->handle(GenericEventMessage::asEventMessage($event));
        return $this;
    }

    /**
     * Indicates that no relevant activity has occurred in the past.
     *
     * @return WhenStateInterface an object that allows the definition of the activity to measure Saga behavior
     */
    public function givenNoPriorActivity()
    {
        return $this;
    }

    /**
     * Returns the time as "known" by the fixture. This is the time at which the fixture was created, plus the amount
     * of
     * time the fixture was told to simulate a "wait".
     * <p/>
     * This time can be used to predict calculations that the saga may have made based on timestamps from the events it
     * received.
     *
     * @return \DateTime the simulated "current time" of the fixture.
     */
    public function currentTime()
    {
        // TODO: Implement currentTime() method.
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
     * @return WhenAggregateEventPublisherInterface an object that allows registration of the actual events to send
     */
    public function whenAggregate($aggregateIdentifier)
    {
        $this->fixtureExecutionResult->startRecording();

        return $this->getPublisherFor($aggregateIdentifier);
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
        try {
            $this->fixtureExecutionResult->startRecording();

            $this->sagaManager->handle(GenericEventMessage::asEventMessage($event));
        } finally {

            //FixtureResourceParameterResolverFactory.clear();
        }

        return $this->fixtureExecutionResult;
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

    private function getPublisherFor($aggregateIdentifier)
    {
        if (!array_key_exists($aggregateIdentifier, $this->aggregatePublishers)) {
            $this->aggregatePublishers[$aggregateIdentifier] = new AggregateEventPublisherImpl(
                $this,
                $aggregateIdentifier
            );
        }

        return $this->aggregatePublishers[$aggregateIdentifier];
    }

}