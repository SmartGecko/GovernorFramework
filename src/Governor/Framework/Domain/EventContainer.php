<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Domain;

/**
 * Description of EventContainer
 *
 * @author david
 */
class EventContainer
{

    private $events = array();
    private $aggregateId;
    private $lastCommitedScn;
    private $lastScn;
    private $registrationCallbacks = array();

    public function __construct($aggregateId)
    {
        $this->aggregateId = $aggregateId;
    }

    public function addEvent(MetaData $metadata, $payload)
    {        
        $event = new GenericDomainEventMessage($this->aggregateId,
                $this->newScn(), $payload, $metadata);
        
        foreach ($this->registrationCallbacks as $callback) {
            $event = $callback->onRegisteredEvent($event);
        }

        $this->lastScn = $event->getScn();
        $this->events[] = $event;

        return $event;
    }

    /**
     * Returns the sequence number of the event last added to this container.
     *
     * @return the sequence number of the last event
     */
    public function getLastScn()
    {
        if (empty($this->events)) {
            return $this->lastCommitedScn;
        } else if (null === $this->lastScn) {
            $last = end($this->events);
            $this->lastScn = $last->getScn();
        }
        return $this->lastScn;
    }

    public function getLastCommitedScn()
    {
        return $this->lastCommitedScn;
    }

    public function size()
    {
        return count($this->events);
    }

    /**
     * Clears the events in this container. The sequence number is not modified by this call.
     */
    public function commit()
    {
        $this->lastCommitedScn = $this->getLastScn();
        $this->events = array();
        $this->registrationCallbacks = array();
    }

    /**
     * Sets the first sequence number that should be assigned to an incoming event.
     *
     * @param $lastKnownScn the sequence number of the last known event
     */
    public function initializeSequenceNumber($lastKnownScn)
    {
        if (0 !== count($this->events)) {
            throw new \RuntimeException("Cannot set first sequence number if events have already been added");
        }

        $this->lastCommitedScn = $lastKnownScn;
    }

    private function newScn()
    {
        $currentScn = $this->getLastScn();

        if (null === $currentScn) {
            return 0;
        }

        return $currentScn + 1;
    }

    public function getEventStream()
    {
        return new SimpleDomainEventStream($this->events);
    }

    public function getAggregateId()
    {
        return $this->aggregateId;
    }

    public function getEventList()
    {
        return $this->events;
    }

    public function addEventRegistrationCallback(EventRegistrationCallbackInterface $eventRegistrationCallback)
    {        
        $this->registrationCallbacks[] = $eventRegistrationCallback;

        foreach ($this->events as $event) {            
            $event = $eventRegistrationCallback->onRegisteredEvent($event);
        }
    }

}
