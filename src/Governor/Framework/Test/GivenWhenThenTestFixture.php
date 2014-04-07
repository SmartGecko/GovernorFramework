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
use Governor\Framework\Domain\AggregateRootInterface;
use Governor\Framework\Domain\GenericDomainEventMessage;
use Governor\Framework\Domain\MessageInterface;
use Governor\Framework\Repository\RepositoryInterface;
use Governor\Framework\EventHandling\EventBusInterface;
use Governor\Framework\CommandHandling\SimpleCommandBus;
use Governor\Framework\CommandHandling\GenericCommandMessage;
use Governor\Framework\CommandHandling\CommandCallbackInterface;
use Governor\Framework\CommandHandling\CommandHandlerInterface;
use Governor\Framework\EventHandling\EventListenerInterface;
use Governor\Framework\EventSourcing\EventSourcingRepository;
use Governor\Framework\EventStore\EventStoreInterface;
use Governor\Framework\Domain\DomainEventStreamInterface;
use Governor\Framework\Domain\SimpleDomainEventStream;

/**
 * Description of GivenWhenThenTestFixture
 *
 * @author david
 */
class GivenWhenThenTestFixture implements FixtureConfigurationInterface, TestExecutorInterface
{

    private $repository;
    private $commandBus;
    private $eventBus;
    private $aggregateIdentifier;
    private $eventStore;
    private $givenEvents;
    private $storedEvents; //Deque<DomainEventMessage> storedEvents;
    private $publishedEvents; //List<EventMessage> publishedEvents;
    private $sequenceNumber = 0;
    private $workingAggregate;
    private $reportIllegalStateChange = true;
    private $aggregateType;
    private $explicitCommandHandlersSet;

    /**
     * Initializes a new given-when-then style test fixture for the given <code>aggregateType</code>.
     *
     * @param aggregateType The aggregate to initialize the test fixture for
     */
    public function __construct($aggregateType)
    {
        $this->eventBus = new RecordingEventBus(&$this->publishedEvents);
        $this->commandBus = new SimpleCommandBus();
        $this->eventStore = new RecordingEventStore();
//FixtureResourceParameterResolverFactory.clear();
//FixtureResourceParameterResolverFactory.registerResource(eventBus);
//FixtureResourceParameterResolverFactory.registerResource(commandBus);
//FixtureResourceParameterResolverFactory.registerResource(eventStore);
        $this->aggregateType = $aggregateType;
//clearGivenWhenState();
    }

    public function registerRepository(EventSourcingRepository $eventSourcingRepository)
    {
        $this->repository = new IdentifierValidatingRepository($eventSourcingRepository);
        $eventSourcingRepository->setEventBus($this->eventBus);
        return $this;
    }

    public function registerAggregateFactory(AggregateFactoryInterface $aggregateFactory)
    {
        return $this->registerRepository(new EventSourcingRepository($aggregateFactory,
                        $this->eventStore));
    }

    public function registerAnnotatedCommandHandler($annotatedCommandHandler)
    {
        $this->registerAggregateCommandHandlers();
        $this->explicitCommandHandlersSet = true;
        /*    AnnotationCommandHandlerAdapter adapter = new AnnotationCommandHandlerAdapter(
          annotatedCommandHandler, ClasspathParameterResolverFactory.forClass(aggregateType));
          for (String supportedCommand : adapter.supportedCommands()) {
          commandBus.subscribe(supportedCommand, adapter);
          } */
        return $this;
    }

    public function registerCommandHandler($commandName,
            CommandHandlerInterface $commandHandler)
    {
        $this->registerAggregateCommandHandlers();
        $this->explicitCommandHandlersSet = true;
        $this->commandBus->subscribe($commandName, $commandHandler);
        return $this;
    }

    public function registerInjectableResource($resource)
    {
        if ($this->explicitCommandHandlersSet) {
            throw new FixtureExecutionException("Cannot inject resources after command handler has been created. " .
            "Configure all resource before calling " .
            "registerCommandHandler() or " .
            "registerAnnotatedCommandHandler()");
        }
//   FixtureResourceParameterResolverFactory.registerResource(resource);
        return $this;
    }

    public function givenNoPriorActivity()
    {
        return $this->given(array());
    }

    public function given(array $domainEvents)
    {
        $this->ensureRepositoryConfiguration();
        $this->clearGivenWhenState();
        try {
            foreach ($domainEvents as $event) {
                $payload = event;
                $metaData = null;
                if ($event instanceof MessageInterface) {
                    $payload = $event->getPayload();
                    $metaData = $event->getMetaData();
                }
                $this->givenEvents[] = new GenericDomainEventMessage($aggregateIdentifier,
                        $this->sequenceNumber++, $payload, $metaData);
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
                $this->commandBus->dispatch(GenericCommandMessage::asCommandMessage($command),
                        $callback);
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
            $resultValidator = new ResultValidatorImpl($this->storedEvents,
                    $this->publishedEvents);
            $this->commandBus->setHandlerInterceptors(array(new AggregateRegisteringInterceptor()));

            $this->commandBus->dispatch(GenericCommandMessage::asCommandMessage($command)->andMetaData($metaData),
                    $resultValidator);

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
            $this->registerRepository(new EventSourcingRepository($this->aggregateType,
                    $this->eventStore));
        }
    }

    private function finalizeConfiguration()
    {
        $this->registerAggregateCommandHandlers();
        $this->explicitCommandHandlersSet = true;
    }

    /*
      private void registerAggregateCommandHandlers() {
      ensureRepositoryConfiguration();
      if (!explicitCommandHandlersSet) {
      AggregateAnnotationCommandHandler<T> handler =
      new AggregateAnnotationCommandHandler<T>(aggregateType, repository,
      new AnnotationCommandTargetResolver());
      for (String supportedCommand : handler.supportedCommands()) {
      commandBus.subscribe(supportedCommand, handler);
      }
      }
      }

      private void detectIllegalStateChanges() {
      if (aggregateIdentifier != null && workingAggregate != null && reportIllegalStateChange) {
      UnitOfWork uow = DefaultUnitOfWork.startAndGet();
      try {
      EventSourcedAggregateRoot aggregate2 = repository.load(aggregateIdentifier);
      if (workingAggregate.isDeleted()) {
      throw new AxonAssertionError("The working aggregate was considered deleted, "
      + "but the Repository still contains a non-deleted copy of "
      + "the aggregate. Make sure the aggregate explicitly marks "
      + "itself as deleted in an EventHandler.");
      }
      assertValidWorkingAggregateState(aggregate2);
      } catch (AggregateNotFoundException notFound) {
      if (!workingAggregate.isDeleted()) {
      throw new AxonAssertionError("The working aggregate was not considered deleted, " //NOSONAR
      + "but the Repository cannot recover the state of the "
      + "aggregate, as it is considered deleted there.");
      }
      } catch (RuntimeException e) {
      logger.warn("An Exception occurred while detecting illegal state changes in {}.",
      workingAggregate.getClass().getName(),
      e);
      } finally {
      // rollback to prevent changes bing pushed to event store
      uow.rollback();
      }
      }
      }

      private void assertValidWorkingAggregateState(EventSourcedAggregateRoot eventSourcedAggregate) {
      HashSet<ComparationEntry> comparedEntries = new HashSet<ComparationEntry>();
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
      comparedEntries);
      }

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
      }

      private void clearGivenWhenState() {
      storedEvents = new LinkedList<DomainEventMessage>();
      publishedEvents = new ArrayList<EventMessage>();
      givenEvents = new ArrayList<DomainEventMessage>();
      sequenceNumber = 0;
      }
     */

    public function setReportIllegalStateChange($reportIllegalStateChange)
    {
        $this->reportIllegalStateChange = $reportIllegalStateChange;
    }

    public function getCommandBus()
    {
        return $this->commandBus;
    }

    public function getEventBus()
    {
        return $this->eventBus;
    }

    public function getEventStore()
    {
        return $this->eventStore;
    }

    public function getRepository()
    {
        $this->ensureRepositoryConfiguration();
        return $this->repository;
    }

    /*

      private class AggregateRegisteringInterceptor implements CommandHandlerInterceptor {

      @Override
      public Object handle(CommandMessage<?> commandMessage, UnitOfWork unitOfWork,
      InterceptorChain interceptorChain)
      throws Throwable {
      unitOfWork.registerListener(new UnitOfWorkListenerAdapter() {
      @Override
      public void onPrepareCommit(UnitOfWork unitOfWork, Set<AggregateRoot> aggregateRoots,
      List<EventMessage> events) {
      Iterator<AggregateRoot> iterator = aggregateRoots.iterator();
      if (iterator.hasNext()) {
      workingAggregate = iterator.next();
      }
      }
      });
      return interceptorChain.proceed();
      }
      }

      private static class ComparationEntry {

      private final Object workingObject;
      private final Object eventSourceObject;

      public ComparationEntry(Object workingObject, Object eventSourceObject) {
      this.workingObject = workingObject;
      this.eventSourceObject = eventSourceObject;
      }

      @SuppressWarnings("RedundantIfStatement")
      @Override
      public boolean equals(Object o) {
      if (this == o) {
      return true;
      }
      if (o == null || getClass() != o.getClass()) {
      return false;
      }

      ComparationEntry that = (ComparationEntry) o;

      if (!eventSourceObject.equals(that.eventSourceObject)) {
      return false;
      }
      if (!workingObject.equals(that.workingObject)) {
      return false;
      }

      return true;
      }

      @Override
      public int hashCode() {
      int result = workingObject.hashCode();
      result = 31 * result + eventSourceObject.hashCode();
      return result;
      }
      }
     */
}

class RecordingEventBus implements EventBusInterface
{

    private $publishedEvents;

    public function __construct($publishedEvents)
    {
        $this->publishedEvents = $publishedEvents;
    }

    public function publish($events)
    {
        $this->publishedEvents = array_merge($this->publishedEvents, $events);
    }

    public function subscribe(EventListenerInterface $eventListener)
    {
        
    }

    public function unsubscribe(EventListenerInterface $eventListener)
    {
        
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
        $aggregate = $this->delegate->load($aggregateIdentifier,
                $expectedVersion);
        $this->validateIdentifier($aggregateIdentifier, $aggregate);
        return $aggregate;
    }

    private function validateIdentifier($aggregateIdentifier,
            AggregateRootInterface $aggregate)
    {
        if (null !== $aggregateIdentifier && !$aggregateIdentifier === $aggregate->getIdentifier()) {
            throw new \RuntimeException(sprintf(
                    "The aggregate used in this fixture was initialized with an identifier different than " .
                    "the one used to load it. Loaded [%s], but actual identifier is [%s].\n" .
                    "Make sure the identifier passed in the Command matches that of the given Events.",
                    $aggregateIdentifier, $aggregate->getIdentifier()));
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

    private $givenEvents;
    private $storedEvents;

    public function __construct($givenEvents, $storedEvents)
    {
        $this->givenEvents = $givenEvents;
        $this->storedEvents = $storedEvents;
    }

    public function appendEvents($type, DomainEventStreamInterface $events)
    {
        while ($events->hasNext()) {
            $next = $events->next();
            $this->validateIdentifier($next->getAggregateIdentifier());
            
            if (!empty($this->storedEvents)) {
                $lastEvent = end($this->storedEvents);
                
                if ($lastEvent->getAggregateIdentifier() !== $next . getAggregateIdentifier()) {
                    throw new EventStoreException("Writing events for an unexpected aggregate. This could " .
                    "indicate that a wrong aggregate is being triggered.");
                } else if ($lastEvent->getScn() !== $next . getScn() - 1) {
                    throw new EventStoreException(sprintf("Unexpected sequence number on stored event. " .
                            "Expected %s, but got %s.",
                            $lastEvent->getScn() + 1, $next->getScn()));
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
            $this->validateIdentifier($identifier);
        }
        
        if (null !== $this->aggregateIdentifier && $aggregateIdentifier !== $identifier) {
            throw new EventStoreException("You probably want to use aggregateIdentifier() on your fixture " .
            "to get the aggregate identifier to use");
        } else if (null === $this->aggregateIdentifier) {
            $this->aggregateIdentifier = $identifier;
            $this->injectAggregateIdentifier();
        }
        
        $allEvents = $this->givenEvents;
        $allEvents = array_merge($allEvents, $this->storedEvents);
        
        if (empty($allEvents)) {
            throw new AggregateNotFoundException($identifier,
            "No 'given' events were configured for this aggregate, " .
            "nor have any events been stored.");
        }
        
        return new SimpleDomainEventStream($allEvents);
    }

    private function injectAggregateIdentifier()
    {
        $oldEvents = $this->givenEvents;
        $this->givenEvents = array();

        foreach ($oldEvents as $oldEvent) {
            if (null !== $oldEvent->getAggregateIdentifier()) {
                $this->givenEvents[] = new GenericDomainEventMessage($oldEvent->getIdentifier(),
                        $oldEvent->getTimestamp(), $this->aggregateIdentifier,
                        $oldEvent->getScn(), $oldEvent->getPayload(),
                        $oldEvent->getMetaData());
            } else {
                $this->givenEvents[] = $oldEvent;
            }
        }
    }

}
