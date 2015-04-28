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

namespace Governor\Framework\UnitOfWork;

use Governor\Framework\Domain\AggregateRootInterface;
use Governor\Framework\EventHandling\EventBusInterface;
use Governor\Framework\Domain\EventMessageInterface;
use Governor\Framework\Domain\DomainEventMessageInterface;

/**
 * DefaultUnitOfWork.
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
class DefaultUnitOfWork extends NestableUnitOfWork
{

    /**
     * @var AggregateRootInterface[]
     */
    private $registeredAggregates = [];

    /**
     * @var \SplObjectStorage
     */
    private $eventsToPublish;

    /**
     * @var UnitOfWorkListenerCollection
     */
    private $listeners;

    /**
     * @var integer
     */
    private $dispatcherStatus;

    /**
     * @var TransactionManagerInterface
     */
    private $transactionManager;

    /**
     * @var mixed
     */
    private $transaction;

    const STATUS_READY = 0;
    const STATUS_DISPATCHING = 1;

    public function __construct(TransactionManagerInterface $transactionManager = null)
    {
        parent::__construct();

        $this->listeners = new UnitOfWorkListenerCollection();
        $this->eventsToPublish = new \SplObjectStorage();
        $this->dispatcherStatus = self::STATUS_READY;
        $this->transactionManager = $transactionManager;
    }

    /**
     *
     * @param TransactionManagerInterface $transactionManager
     * @return UnitOfWorkInterface
     */
    public static function startAndGet(TransactionManagerInterface $transactionManager = null)
    {
        $uow = new DefaultUnitOfWork($transactionManager);
        $uow->start();

        return $uow;
    }

    public function isTransactional()
    {
        return null !== $this->transactionManager;
    }

    protected function doCommit()
    {
        $this->publishEvents();
        $this->commitInnerUnitOfWork();

        if ($this->isTransactional()) {
            $this->notifyListenersPrepareTransactionCommit(null);
            $this->transactionManager->commitTransaction($this->transaction);
        }

        $this->notifyListenersAfterCommit();
    }

    protected function doRollback(\Exception $ex = null)
    {
        $this->registeredAggregates = array();
        $this->eventsToPublish = new \SplObjectStorage();

        try {
            if (null !== $this->transaction) {
                $this->transactionManager->rollbackTransaction($this->transaction);
            }
        } finally {
            $this->notifyListenersRollback($ex);
        }
    }

    protected function notifyListenersRollback(\Exception $ex = null)
    {
        $this->listeners->onRollback($this, $ex);
    }

    public function registerAggregate(
        AggregateRootInterface $aggregateRoot,
        EventBusInterface $eventBus,
        SaveAggregateCallbackInterface $saveAggregateCallback
    ) {
        $similarAggregate = $this->findSimilarAggregate(
            get_class($aggregateRoot),
            $aggregateRoot->getIdentifier()
        );
        if (null !== $similarAggregate) {

            $this->logger->info(
                "Ignoring aggregate registration. An aggregate of same type and identifier was already".
                "registered in this Unit Of Work: type [{aggregate}], identifier [{identifier}]",
                array('aggregate' => get_class($aggregateRoot), 'identifier' => $aggregateRoot->getIdentifier())
            );

            return $similarAggregate;
        }

        $uow = $this;
        $eventRegistrationCallback = new UoWEventRegistrationCallback(
            function (DomainEventMessageInterface $event) use ($uow, $eventBus) {
                $event = $uow->invokeEventRegistrationListeners($event);
                $uow->eventsToPublishOn($event, $eventBus);

                return $event;
            }
        );

        $this->registeredAggregates[spl_object_hash($aggregateRoot)] = array(
            $aggregateRoot,
            $saveAggregateCallback
        );

        $this->logger->debug(
            "Registering aggregate {aggregate}",
            array('aggregate' => get_class($aggregateRoot))
        );

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
                === $identifier
            ) {
                return $aggregate;
            }
        }

        return null;
    }

    private function eventsToPublishOn(
        EventMessageInterface $event,
        EventBusInterface $eventBus
    ) {
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

    public function publishEvent(
        EventMessageInterface $event,
        EventBusInterface $eventBus
    ) {
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
            $this->logger->debug(
                "UnitOfWork is already in the dispatch process. ".
                "That process will publish events instead. Aborting..."
            );

            return;
        }
        $this->dispatcherStatus = self::STATUS_DISPATCHING;
        $this->eventsToPublish->rewind();

        while ($this->eventsToPublish->valid()) {
            $bus = $this->eventsToPublish->current();
            $events = $this->eventsToPublish->getInfo();

            foreach ($events as $event) {
                $this->logger->debug(
                    "Publishing event [{event}] to event bus [{bus}]",
                    array('event' => $event->getPayloadType(), 'bus' => get_class($bus))
                );
            }

            // clear and send
            $this->eventsToPublish->setInfo(array());
            $bus->publish($events);

            $this->eventsToPublish->next();
        }

        $this->logger->debug("All events successfully published.");
        $this->dispatcherStatus = self::STATUS_READY;
    }

    protected function doStart()
    {
        if ($this->isTransactional()) {
            $this->transaction = $this->transactionManager->startTransaction();
        }
    }

    protected function notifyListenersPrepareCommit()
    {
        $list = array();

        foreach ($this->registeredAggregates as $aggregateEntry) {
            $list[] = $aggregateEntry[0];
        }

        $this->listeners->onPrepareCommit($this, $list, $this->eventsToPublish());
    }

    /**
     * Send a {@link UnitOfWorkListener#afterCommit(UnitOfWork)} notification to all registered listeners.
     *
     * @param mixed $transaction The object representing the transaction to about to be committed
     */
    protected function notifyListenersPrepareTransactionCommit($transaction)
    {
        $this->listeners->onPrepareTransactionCommit($this, $transaction);
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

            $this->logger->debug(
                "Persisting changes to [{aggregate}], identifier: [{id}]",
                array(
                    'aggregate' => get_class($aggregate),
                    'id' => $aggregate->getIdentifier()
                )
            );

            $callback->save($aggregate);
        }
        $this->logger->debug("Aggregates successfully persisted");
        $this->registeredAggregates = array();
    }

}
