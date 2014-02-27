<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Domain;

/**
 * Description of AggregateRootInterface
 *
 * @author david
 */
interface AggregateRootInterface
{

    /**
     * Returns the if of this aggregate.
     *
     * @return mixed the identifier of this aggregate
     */
    public function getIdentifier();

    /**
     * Clears the events currently marked as "uncommitted"
     * 
     */
    public function commitEvents();

    /**
     * Returns the number of uncommitted events currently available in the aggregate.
     *
     * @return integer
     */
    public function getUncommittedEventCount();

    /**
     * Returns a DomainEventStream to the events in the aggregate that have been raised since creation or the last
     * commit.
     *
     * @return DomainEventStreamInterface the DomainEventStream to the uncommitted events.
     */
    public function getUncommittedEvents();

    /**
     * Returns the current version number of the aggregate, or <code>null</code> if the aggregate is newly created.
     * This
     * version must reflect the version number of the aggregate on which changes are applied.
     * <p/>
     * Each time the aggregate is <em>modified and stored</em> in a repository, the version number must be increased by
     * at least 1. This version number can be used by optimistic locking strategies and detection of conflicting
     * concurrent modification.
     * <p/>
     * Typically the sequence number of the last committed event on this aggregate is used as version number.
     *
     * @return integer
     */
    public function getVersion();

    /**
     * Indicates whether this aggregate has been marked as deleted.
     * @return boolean
     */
    public function isDeleted();

    /**
     * Adds an EventRegistrationCallback, which is notified when the aggregate registers an Event for publication.
     * These callbacks are cleared when the aggregate is committed.
     * <p/>
     * If the aggregate contains uncommitted events, they are all passed to the given
     * <code>eventRegistrationCallback</code> for processing.
     *
     * @param EventRegistrationCallbackInterface $eventRegistrationCallback the callback to notify when an event is registered
     */
    public function addEventRegistrationCallback(EventRegistrationCallbackInterface $eventRegistrationCallback);
}
