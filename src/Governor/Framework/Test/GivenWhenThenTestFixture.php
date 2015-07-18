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

use Governor\Framework\Annotations\CommandHandler;
use Governor\Framework\CommandHandling\CommandBusInterface;
use Governor\Framework\CommandHandling\CommandCallbackInterface;
use Governor\Framework\CommandHandling\CommandHandlerInterceptorInterface;
use Governor\Framework\CommandHandling\CommandHandlerInterface;
use Governor\Framework\CommandHandling\CommandMessageInterface;
use Governor\Framework\CommandHandling\GenericCommandMessage;
use Governor\Framework\CommandHandling\Handlers\AnnotatedAggregateCommandHandler;
use Governor\Framework\CommandHandling\Handlers\AnnotatedCommandHandler;
use Governor\Framework\CommandHandling\InterceptorChainInterface;
use Governor\Framework\CommandHandling\SimpleCommandBus;
use Governor\Framework\Common\Annotation\MethodMessageHandlerInspector;
use Governor\Framework\Common\Annotation\SimpleAnnotationReaderFactory;
use Governor\Framework\Common\IdentifierValidator;
use Governor\Framework\Domain\AggregateRootInterface;
use Governor\Framework\Domain\DomainEventMessageInterface;
use Governor\Framework\Domain\DomainEventStreamInterface;
use Governor\Framework\Domain\EventMessageInterface;
use Governor\Framework\Domain\GenericDomainEventMessage;
use Governor\Framework\Domain\MessageInterface;
use Governor\Framework\Domain\SimpleDomainEventStream;
use Governor\Framework\EventHandling\EventBusInterface;
use Governor\Framework\EventSourcing\AggregateFactoryInterface;
use Governor\Framework\EventSourcing\EventSourcedAggregateRootInterface;
use Governor\Framework\EventSourcing\EventSourcingRepository;
use Governor\Framework\EventSourcing\GenericAggregateFactory;
use Governor\Framework\EventStore\EventStoreException;
use Governor\Framework\EventStore\EventStoreInterface;
use Governor\Framework\Repository\AggregateNotFoundException;
use Governor\Framework\Repository\NullLockManager;
use Governor\Framework\Repository\RepositoryInterface;
use Governor\Framework\Test\Utils\RecordingEventBus;
use Governor\Framework\UnitOfWork\DefaultUnitOfWork;
use Governor\Framework\UnitOfWork\DefaultUnitOfWorkFactory;
use Governor\Framework\UnitOfWork\UnitOfWorkInterface;
use Governor\Framework\UnitOfWork\UnitOfWorkListenerAdapter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;


/**
 * Description of GivenWhenThenTestFixture
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
class GivenWhenThenTestFixture implements FixtureConfigurationInterface, TestExecutorInterface
{

    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var IdentifierValidatingRepository
     */
    private $repository;

    /**
     * @var SimpleCommandBus
     */
    private $commandBus;
    /**
     * @var RecordingEventBus
     */
    private $eventBus;

    /**
     * @var string
     */
    private $aggregateIdentifier;

    /**
     * @var RecordingEventStore
     */
    private $eventStore;

    /**
     * @var DomainEventMessageInterface[]
     */
    private $givenEvents = [];

    /**
     * @var DomainEventMessageInterface[]
     */
    private $storedEvents = array(); //Deque<DomainEventMessage> storedEvents;

    /**
     * @var EventMessageInterface[]
     */
    private $publishedEvents = array(); //List<EventMessage> publishedEvents;
    /**
     * @var int
     */
    private $sequenceNumber = 0;

    /**
     * @var AggregateRootInterface
     */
    private $workingAggregate;

    /**
     * @var bool
     */
    private $reportIllegalStateChange = true;
    /**
     * @var string
     */
    private $aggregateType;

    /**
     * @var boolean
     */
    private $explicitCommandHandlersSet;

    /**
     * @var FixtureParameterResolverFactory
     */
    private $parameterResolver;

    /**
     * Initializes a new given-when-then style test fixture for the given <code>aggregateType</code>.
     *
     * @param string $aggregateType The aggregate to initialize the test fixture for
     */
    public function __construct($aggregateType)
    {
        $this->logger = new Logger('fixture');
        $this->logger->pushHandler(
            new StreamHandler(
                'php://stdout',
                Logger::DEBUG
            )
        );

        $this->eventBus = new RecordingEventBus($this->publishedEvents);
        $this->commandBus = new SimpleCommandBus(new DefaultUnitOfWorkFactory());
        $this->commandBus->setLogger($this->logger);
        $this->eventStore = new RecordingEventStore(
            $this->storedEvents,
            $this->givenEvents, $this->aggregateIdentifier
        );

        $this->parameterResolver = new FixtureParameterResolverFactory();

        $this->aggregateType = $aggregateType;
        $this->clearGivenWhenState();
    }

    /**
     * @return Logger
     */
    public function getLogger()
    {
        return $this->logger;
    }

    public function registerRepository(EventSourcingRepository $eventSourcingRepository)
    {
        $this->repository = new IdentifierValidatingRepository($eventSourcingRepository);

        return $this;
    }

    public function registerAggregateFactory(AggregateFactoryInterface $aggregateFactory)
    {
        return $this->registerRepository(
            new EventSourcingRepository(
                $aggregateFactory->getAggregateType(),
                $this->eventBus, new NullLockManager(),
                $this->eventStore, $aggregateFactory
            )
        );
    }

    public function registerAnnotatedCommandHandler($annotatedCommandHandler)
    {
        $this->registerAggregateCommandHandlers();
        $this->explicitCommandHandlersSet = true;

        $reflectionClass = new \ReflectionClass($annotatedCommandHandler);
        $inspector = new MethodMessageHandlerInspector(
            new SimpleAnnotationReaderFactory(),
            $reflectionClass,
            CommandHandler::class
        );

        foreach ($inspector->getHandlerDefinitions() as $handlerDefinition) {
            $handler = new AnnotatedCommandHandler(
                $reflectionClass->name,
                $handlerDefinition->getMethod()->name,
                $this->parameterResolver,
                $annotatedCommandHandler
            );

            $this->commandBus->subscribe($handlerDefinition->getPayloadType(), $handler);
        }

        return $this;
    }

    public function registerCommandHandler(
        $commandName,
        CommandHandlerInterface $commandHandler
    ) {
        $this->registerAggregateCommandHandlers();
        $this->explicitCommandHandlersSet = true;
        $this->commandBus->subscribe($commandName, $commandHandler);

        return $this;
    }

    public function registerInjectableResource($id, $resource)
    {
        if ($this->explicitCommandHandlersSet) {
            throw new FixtureExecutionException(
                "Cannot inject resources after command handler has been created. ".
                "Configure all resource before calling ".
                "registerCommandHandler() or ".
                "registerAnnotatedCommandHandler()"
            );
        }

        $this->parameterResolver->registerService($id, $resource);

        return $this;
    }

    public function givenNoPriorActivity()
    {
        return $this->given(array());
    }

    public function given(array $domainEvents = array())
    {
        $this->ensureRepositoryConfiguration();
        $this->clearGivenWhenState();
        try {
            foreach ($domainEvents as $event) {
                $payload = $event;
                $metaData = null;
                if ($event instanceof MessageInterface) {
                    $payload = $event->getPayload();
                    $metaData = $event->getMetaData();
                }
                $this->givenEvents[] = new GenericDomainEventMessage(
                    $this->aggregateIdentifier,
                    $this->sequenceNumber++, $payload, $metaData
                );
            }
        } catch (\RuntimeException $ex) {
//FixtureResourceParameterResolverFactory.clear();
        }

        return $this;
    }

    public function givenCommands(array $commands)
    {
        $this->finalizeConfiguration();
        $this->clearGivenWhenState();
        try {
            foreach ($commands as $command) {
                $callback = new ExecutionExceptionAwareCallback();
                $this->commandBus->dispatch(
                    GenericCommandMessage::asCommandMessage($command),
                    $callback
                );
                $callback->assertSuccessful();

                foreach ($this->storedEvents as $event) {
                    $this->givenEvents[] = $event;
                }

                $this->storedEvents = array();
            }
            $this->publishedEvents = array();
        } catch (\RuntimeException $ex) {
            //FixtureResourceParameterResolverFactory.clear();
            throw $ex;
        }

        return $this;
    }

    public function when($command, array $metaData = array())
    {
        try {
            $this->finalizeConfiguration();
            $resultValidator = new ResultValidatorImpl(
                $this->storedEvents,
                $this->publishedEvents
            );
            $this->commandBus->setHandlerInterceptors(
                array(new AggregateRegisteringInterceptor($this->workingAggregate))
            );

            $this->commandBus->dispatch(
                GenericCommandMessage::asCommandMessage($command)->andMetaData($metaData),
                $resultValidator
            );

            $this->detectIllegalStateChanges();
            $resultValidator->assertValidRecording();

            return $resultValidator;
        } finally {
            //FixtureResourceParameterResolverFactory.clear();
        }
    }

    private function ensureRepositoryConfiguration()
    {
        if (null === $this->repository) {
            $this->registerRepository(
                new EventSourcingRepository(
                    $this->aggregateType,
                    $this->eventBus,
                    new NullLockManager(),
                    $this->eventStore,
                    new GenericAggregateFactory($this->aggregateType)
                )
            );
        }
    }

    private function finalizeConfiguration()
    {
        $this->registerAggregateCommandHandlers();
        $this->explicitCommandHandlersSet = true;
    }

    private function registerAggregateCommandHandlers()
    {
        $this->ensureRepositoryConfiguration();

        if (!$this->explicitCommandHandlersSet) {
            AnnotatedAggregateCommandHandler::subscribe(
                $this->aggregateType,
                $this->repository,
                $this->commandBus,
                $this->parameterResolver,
                null,
                new SimpleAnnotationReaderFactory()
            );
        }
    }

    private function detectIllegalStateChanges()
    {
        if (null !== $this->aggregateIdentifier && null !== $this->workingAggregate
            && $this->reportIllegalStateChange
        ) {
            $uow = DefaultUnitOfWork::startAndGet();
            try {
                $aggregate2 = $this->repository->load($this->aggregateIdentifier);
                if ($this->workingAggregate->isDeleted()) {
                    throw new GovernorAssertionError(
                        "The working aggregate was considered deleted, ".
                        "but the Repository still contains a non-deleted copy of ".
                        "the aggregate. Make sure the aggregate explicitly marks ".
                        "itself as deleted in an EventHandler."
                    );
                }
                $this->assertValidWorkingAggregateState($aggregate2);
            } catch (AggregateNotFoundException $notFound) {
                if (!$this->workingAggregate->isDeleted()) {
                    throw new GovernorAssertionError(
                        "The working aggregate was not considered deleted, ".
                        "but the Repository cannot recover the state of the ".
                        "aggregate, as it is considered deleted there."
                    );
                }
            } catch (\Exception $ex) {
                $this->logger->warn(
                    "An Exception occurred while detecting illegal state changes in {class}.",
                    array('class' => get_class($this->workingAggregate)),
                    $ex
                );
            } finally {
                // rollback to prevent changes bing pushed to event store
                $uow->rollback();
            }
        }
    }


    private function assertValidWorkingAggregateState(EventSourcedAggregateRootInterface $eventSourcedAggregate)
    {
        /*HashSet<ComparationEntry> comparedEntries = new HashSet<ComparationEntry>();
        if (!workingAggregate.getClass().equals(eventSourcedAggregate.getClass())) {
        throw new AxonAssertionError(String.format("The aggregate loaded based on the generated events seems to "
        + "be of another type than the original.\n"
        + "Working type: <%s>\nEvent Sourced type: <%s>",
        workingAggregate.getClass().getName(),
        eventSourcedAggregate.getClass().getName()));
        }
        ensureValuesEqual(workingAggregate,
        eventSourcedAggregate,
        eventSourcedAggregate.getClass().getName(),
        comparedEntries);*/
    }

    /*
          private void ensureValuesEqual(Object workingValue, Object eventSourcedValue, String propertyPath,
          Set<ComparationEntry> comparedEntries) {
          if (explicitlyUnequal(workingValue, eventSourcedValue)) {
          throw new AxonAssertionError(format("Illegal state change detected! "
          + "Property \"%s\" has different value when sourcing events.\n"
          + "Working aggregate value:     <%s>\n"
          + "Value after applying events: <%s>",
          propertyPath, workingValue, eventSourcedValue));
          } else if (workingValue != null && comparedEntries.add(new ComparationEntry(workingValue, eventSourcedValue))
          && !hasEqualsMethod(workingValue.getClass())) {
          for (Field field : fieldsOf(workingValue.getClass())) {
          if (!Modifier.isStatic(field.getModifiers()) && !Modifier.isTransient(field.getModifiers())) {
          ensureAccessible(field);
          String newPropertyPath = propertyPath + "." + field.getName();
          try {
          Object workingFieldValue = field.get(workingValue);
          Object eventSourcedFieldValue = field.get(eventSourcedValue);
          ensureValuesEqual(workingFieldValue, eventSourcedFieldValue, newPropertyPath, comparedEntries);
          } catch (IllegalAccessException e) {
          logger.warn("Could not access field \"{}\". Unable to detect inappropriate state changes.",
          newPropertyPath);
          }
          }
          }
          }
          } */

    private function clearGivenWhenState()
    {
        $this->storedEvents = array();
        $this->publishedEvents = array();
        $this->givenEvents = array();
        $this->sequenceNumber = 0;
    }

    public function setReportIllegalStateChange($reportIllegalStateChange)
    {
        $this->reportIllegalStateChange = $reportIllegalStateChange;
    }

    /**
     * Returns the {@see CommandBusInterface} used by this fixture.
     *
     * @return CommandBusInterface
     */
    public function getCommandBus()
    {
        return $this->commandBus;
    }

    /**
     * Returns the {@see EventBusInterface} used by this fixture.
     *
     * @return EventBusInterface
     */
    public function getEventBus()
    {
        return $this->eventBus;
    }

    /**
     * Returns the {@see EventStoreInterface} used by this fixture.
     *
     * @return EventStoreInterface
     */
    public function getEventStore()
    {
        return $this->eventStore;
    }

    public function getRepository()
    {
        $this->ensureRepositoryConfiguration();

        return $this->repository;
    }

}

class IdentifierValidatingRepository implements RepositoryInterface
{

    private $delegate;

    public function __construct(RepositoryInterface $delegate)
    {
        $this->delegate = $delegate;
    }

    public function load($aggregateIdentifier, $expectedVersion = null)
    {
        $aggregate = $this->delegate->load(
            $aggregateIdentifier,
            $expectedVersion
        );

        $this->validateIdentifier($aggregateIdentifier, $aggregate);

        return $aggregate;
    }

    private function validateIdentifier(
        $aggregateIdentifier,
        AggregateRootInterface $aggregate
    ) {
        if (null !== $aggregateIdentifier && !$aggregateIdentifier === $aggregate->getIdentifier()) {
            throw new \RuntimeException(
                sprintf(
                    "The aggregate used in this fixture was initialized with an identifier different than ".
                    "the one used to load it. Loaded [%s], but actual identifier is [%s].\n".
                    "Make sure the identifier passed in the Command matches that of the given Events.",
                    $aggregateIdentifier,
                    $aggregate->getIdentifier()
                )
            );
        }
    }

    public function add(AggregateRootInterface $aggregate)
    {
        $this->delegate->add($aggregate);
    }

    public function supportsClass($class)
    {
        return true;
    }

}

class ExecutionExceptionAwareCallback implements CommandCallbackInterface
{

    private $exception;

    public function onSuccess($result)
    {

    }

    public function onFailure(\Exception $cause)
    {
        if ($cause instanceof FixtureExecutionException) {
            $this->exception = $cause;
        }
    }

    public function assertSuccessful()
    {
        if (null !== $this->exception) {
            throw $this->exception;
        }
    }

}

class RecordingEventStore implements EventStoreInterface
{

    private $storedEvents;
    private $givenEvents;
    private $aggregateIdentifier;

    public function __construct(
        array &$storedEvents,
        array &$givenEvents,
        &$aggregateIdentifier
    ) {
        $this->storedEvents = &$storedEvents;
        $this->givenEvents = &$givenEvents;
        $this->aggregateIdentifier = &$aggregateIdentifier;
    }

    public function appendEvents($type, DomainEventStreamInterface $events)
    {
        while ($events->hasNext()) {
            $next = $events->next();
            IdentifierValidator::validateIdentifier($next->getAggregateIdentifier());

            if (!empty($this->storedEvents)) {
                $lastEvent = end($this->storedEvents);

                if ($lastEvent->getAggregateIdentifier() !== $next->getAggregateIdentifier()) {
                    throw new EventStoreException(
                        "Writing events for an unexpected aggregate. This could ".
                        "indicate that a wrong aggregate is being triggered."
                    );
                } else {
                    if ($lastEvent->getScn() !== $next->getScn() - 1) {
                        throw new EventStoreException(
                            sprintf(
                                "Unexpected sequence number on stored event. ".
                                "Expected %s, but got %s.",
                                $lastEvent->getScn() + 1,
                                $next->getScn()
                            )
                        );
                    }
                }
            }

            if (null === $this->aggregateIdentifier) {
                $this->aggregateIdentifier = $next->getAggregateIdentifier();
                $this->injectAggregateIdentifier();
            }

            $this->storedEvents[] = $next;
        }
    }

    public function readEvents($type, $identifier)
    {
        if (null !== $identifier) {
            IdentifierValidator::validateIdentifier($identifier);
        }

        if (null !== $this->aggregateIdentifier && $this->aggregateIdentifier !== $identifier) {
            throw new EventStoreException(
                "You probably want to use aggregateIdentifier() on your fixture ".
                "to get the aggregate identifier to use"
            );
        } else {
            if (null === $this->aggregateIdentifier) {
                $this->aggregateIdentifier = $identifier;
                $this->injectAggregateIdentifier();
            }
        }

        $allEvents = $this->givenEvents;
        $allEvents = array_merge($allEvents, $this->storedEvents);

        if (empty($allEvents)) {
            throw new AggregateNotFoundException(
                $identifier,
                "No 'given' events were configured for this aggregate, ".
                "nor have any events been stored."
            );
        }

        return new SimpleDomainEventStream($allEvents);
    }

    private function injectAggregateIdentifier()
    {
        $oldEvents = $this->givenEvents;
        $this->givenEvents = array();

        foreach ($oldEvents as $oldEvent) {
            if (null === $oldEvent->getAggregateIdentifier()) {
                $this->givenEvents[] = new GenericDomainEventMessage(
                    $this->aggregateIdentifier,
                    $oldEvent->getScn(),
                    $oldEvent->getPayload(),
                    $oldEvent->getMetaData(),
                    $oldEvent->getIdentifier(),
                    $oldEvent->getTimestamp()
                );
            } else {
                $this->givenEvents[] = $oldEvent;
            }
        }
    }

    public function setLogger(LoggerInterface $logger)
    {

    }

}

class AggregateRegisteringInterceptor implements CommandHandlerInterceptorInterface
{
    private $workingAggregate;

    function __construct(&$workingAggregate)
    {
        $this->workingAggregate = &$workingAggregate;
    }

    public function handle(
        CommandMessageInterface $commandMessage,
        UnitOfWorkInterface $unitOfWork,
        InterceptorChainInterface $interceptorChain
    ) {

        $unitOfWork->registerListener(new AggregateListenerAdapter($this->workingAggregate));

        return $interceptorChain->proceed();
    }

}

class AggregateListenerAdapter extends UnitOfWorkListenerAdapter
{
    private $workingAggregate;

    function __construct(&$workingAggregate)
    {
        $this->workingAggregate = &$workingAggregate;
    }


    public function onPrepareCommit(
        UnitOfWorkInterface $unitOfWork,
        array $aggregateRoots,
        array $events
    ) {
        foreach ($aggregateRoots as $aggregateRoot) {
            $this->workingAggregate = $aggregateRoot;
        }
    }

}