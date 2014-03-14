<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\UnitOfWork;

use Psr\Log\LoggerInterface;
use Governor\Framework\Domain\AggregateRootInterface;
use Governor\Framework\EventHandling\EventBusInterface;
use Governor\Framework\Domain\EventMessageInterface;
use Governor\Framework\Domain\DomainEventMessageInterface;

/**
 * Description of DefaultUnitOfWork
 *
 * @author david
 */
class DefaultUnitOfWork extends NestableUnitOfWork
{

    private $registeredAggregates = array();

    /**
     *
     * @var \SplObjectStorage
     */
    private $eventsToPublish;

    /**
     * @var \Governor\Framework\UnitOfWork\UnitOfWorkListenerCollection
     */
    private $listeners;
    private $dispatcherStatus;

    const STATUS_READY = 0;
    const STATUS_DISPATCHING = 1;

    public function __construct(LoggerInterface $logger)
    {
        $this->listeners = new UnitOfWorkListenerCollection();
        $this->eventsToPublish = new \SplObjectStorage();
        $this->dispatcherStatus = self::STATUS_READY;
        $this->logger = $logger;
    }

    public static function startAndGet(LoggerInterface $logger)
    {
        $uow = new DefaultUnitOfWork($logger);
        $uow->start();
        return $uow;
    }

    public function isTransactional()
    {
        return false;
    }

    protected function doCommit()
    {
        $this->publishEvents();
        $this->commitInnerUnitOfWork();

        $this->notifyListenersAfterCommit();
    }

    protected function doRollback(\Exception $ex = null)
    {
        $this->registeredAggregates = array();
        $this->eventsToPublish = new \SplObjectStorage();

        $this->notifyListenersRollback($ex);
    }

    protected function notifyListenersRollback(\Exception $ex = null)
    {
        $this->listeners->onRollback($this, $ex);
    }

    public function registerAggregate(AggregateRootInterface $aggregateRoot,
            EventBusInterface $eventBus,
            SaveAggregateCallbackInterface $saveAggregateCallback)
    {
        $similarAggregate = $this->findSimilarAggregate(get_class($aggregateRoot),
                $aggregateRoot->getIdentifier());
        if (null !== $similarAggregate) {

            $this->logger->info("Ignoring aggregate registration. An aggregate of same type and identifier was already" .
                    "registered in this Unit Of Work: type [{aggregate}], identifier [{identifier}]",
                    array('aggregate' => get_class($aggregateRoot), 'identifier' => $aggregateRoot->getIdentifier()));

            return $similarAggregate;
        }

        $uow = $this;
        $eventRegistrationCallback = new UoWEventRegistrationCallback(function (DomainEventMessageInterface $event) use ($uow, $eventBus) {
            $event = $uow->invokeEventRegistrationListeners($event);
            $uow->eventsToPublishOn($event, $eventBus);

            return $event;
        });

        $this->registeredAggregates[spl_object_hash($aggregateRoot)] = array($aggregateRoot,
            $saveAggregateCallback);

        $this->logger->debug("Registering aggregate {aggregate}", array('aggregate' => get_class($aggregateRoot)));
        // listen for new events registered in the aggregate
        $aggregateRoot->addEventRegistrationCallback($eventRegistrationCallback);
        return $aggregateRoot;
    }

    public function registerListener($listener)
    {
        $this->listeners->add($listener);
    }

    private function findSimilarAggregate($aggregateType, $identifier)
    {
        foreach ($this->registeredAggregates as $hash => $aggregateEntry) {
            list ($aggregate, $callback) = $aggregateEntry;

            if (get_class($aggregate) === $aggregateType && $aggregate->getIdentifier()
                    === $identifier) {
                return $aggregate;
            }
        }
        return null;
    }

    private function eventsToPublishOn(EventMessageInterface $event,
            EventBusInterface $eventBus)
    {        
        if (!$this->eventsToPublish->contains($eventBus)) {
            $this->eventsToPublish->attach($eventBus, array($event));     
            return;
        }

        $events = $this->eventsToPublish->offsetGet($eventBus);        
        $events[] = $event;
        $this->eventsToPublish->offsetSet($eventBus, $events);        
    }

    private function invokeEventRegistrationListeners(EventMessageInterface $event)
    {
        return $this->listeners->onEventRegistered($this, $event);
    }

    public function publishEvent(EventMessageInterface $event,
            EventBusInterface $eventBus)
    {                
        $event = $this->invokeEventRegistrationListeners($event);
        $this->eventsToPublishOn($event, $eventBus);
    }

    /**
     * Publishes all registered events to their respective event bus.
     */
    protected function publishEvents()
    {
        $this->logger->debug("Publishing events to the event bus");
        if ($this->dispatcherStatus == self::STATUS_DISPATCHING) {
            // this prevents events from overtaking each other
            $this->logger->debug("UnitOfWork is already in the dispatch process. " .
                    "That process will publish events instead. Aborting...");
            return;
        }
        $this->dispatcherStatus = self::STATUS_DISPATCHING;
        $this->eventsToPublish->rewind();
                
        while ($this->eventsToPublish->valid()) {
            $bus = $this->eventsToPublish->current();
            $events = $this->eventsToPublish->getInfo();                        

            foreach ($events as $event) {
                $this->logger->debug("Publishing event [{event}] to event bus [{bus}]",
                        array('event' => $event->getPayloadType(), 'bus' => get_class($bus)));
            }

            // clear and send
            $this->eventsToPublish->setInfo(array());
            $bus->publish($events);

            $this->eventsToPublish->next();
        }

        /*
          Map.Entry<EventBus, List<EventMessage<?>>> entry = iterator.next();
          List<EventMessage<?>> messageList = entry.getValue();
          EventMessage<?>[] messages = messageList.toArray(new EventMessage<?>[messageList.size()]);
          if (logger.isDebugEnabled()) {
          for (EventMessage message : messages) {
          logger.debug("Publishing event [{}] to event bus [{}]",
          message.getPayloadType().getName(),
          entry.getKey());
          }
          }
          // remove this entry before publication in case a new event is registered with the UoW while publishing
          iterator.remove();
          entry.getKey().publish(messages);
          }
          }
         */
        $this->logger->debug("All events successfully published.");
        $this->dispatcherStatus = self::STATUS_READY;
    }

    protected function doStart()
    {
        
    }

    protected function notifyListenersPrepareCommit()
    {
        $list = array();

        foreach ($this->registeredAggregates as $aggregateEntry) {
            $list[] = $aggregateEntry[0];
        }

        $this->listeners->onPrepareCommit($this, $list, $this->eventsToPublish());
    }

    private function eventsToPublish()
    {        
        $events = array();

        $this->eventsToPublish->rewind();
        while ($this->eventsToPublish->valid()) {            
            $events = array_merge($events, $this->eventsToPublish->getInfo());
            $this->eventsToPublish->next();
        }

        return $events;
    }

    protected function notifyListenersCleanup()
    {
        $this->listeners->onCleanup($this);
    }

    protected function notifyListenersAfterCommit()
    {
        $this->listeners->afterCommit($this);
    }

    protected function saveAggregates()
    {        
        $this->logger->debug("Persisting changes to aggregates");
        foreach ($this->registeredAggregates as $aggregateEntry) {            
            list ($aggregate, $callback) = $aggregateEntry;
                    
            $this->logger->debug("Persisting changes to [{aggregate}], identifier: [{id}]",
                    array('aggregate' => get_class($aggregate), 'id' => $aggregate->getIdentifier()));

            //$aggregate->saveAggregate();            
            $callback->save($aggregate);
            //entry.saveAggregate();
        }
        $this->logger->debug("Aggregates successfully persisted");
        $this->registeredAggregates = array();
    }

}
