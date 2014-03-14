<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\UnitOfWork;

use Governor\Framework\Domain\AggregateRootInterface;
use Governor\Framework\Domain\EventMessageInterface;
use Governor\Framework\EventHandling\EventBusInterface;

/**
 *
 * @author david
 */
interface UnitOfWorkInterface
{

    public function commit();

    public function rollback(\Exception $ex = null);

    public function start();

    public function registerListener($listener);

    /**
     * Indicates whether this UnitOfWork is started. It is started when the {@link #start()} method has been called,
     * and
     * if the UnitOfWork has not been committed or rolled back.
     *
     * @return boolean <code>true</code> if this UnitOfWork is started, <code>false</code> otherwise.
     */
    public function isStarted();

    /**
     * Indicates whether this UnitOfWork is bound to a transaction.
     *
     * @return boolean <code>true</code> if this unit of work is bound to a transaction, otherwise <code>false</code>
     */
    public function isTransactional();

    public function registerAggregate(AggregateRootInterface $aggregateRoot,
        EventBusInterface $eventBus,
        SaveAggregateCallbackInterface $saveAggregateCallback);

    /**
     * Request to publish the given <code>event</code> on the given <code>eventBus</code>. The UnitOfWork may either
     * publish immediately, or buffer the events until the UnitOfWork is committed.
     *
     * @param EventMessageInterface $event    The event to be published on the event bus
     * @param EventBusInterface $eventBus The event bus on which to publish the event
     */
    public function publishEvent(EventMessageInterface $event,
        EventBusInterface $eventBus);
}
