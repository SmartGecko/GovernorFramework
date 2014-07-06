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

namespace Governor\Framework\Domain;

/**
 * Description of EventContainer
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a> 
 */
class EventContainer
{

    private $events = array();
    private $aggregateId;
    private $lastCommitedScn;
    private $lastScn;
    private $registrationCallbacks = array();

    /**     
     * @param mixed $aggregateId
     */
    public function __construct($aggregateId)
    {
        $this->aggregateId = $aggregateId;
    }

    /**
     * Adds an event with the metadata and payload into the eventcontainer.
     * 
     * @param MetaData $metadata
     * @param mixed $payload
     * 
     * @return \Governor\Framework\Domain\GenericDomainEventMessage
     */
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
     * @return integer the sequence number of the last event
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

    /**
     * Returns the last commited scn number.
     * 
     * @return integer
     */
    public function getLastCommitedScn()
    {
        return $this->lastCommitedScn;
    }

    /**
     * Returns the size of the event container.
     * 
     * @return integer
     */
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
     * @param integer $lastKnownScn the sequence number of the last known event
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

    /**
     * Returns an event stream 
     * @return \Governor\Framework\Domain\SimpleDomainEventStream
     */
    public function getEventStream()
    {
        return new SimpleDomainEventStream($this->events);
    }
    
    /**
     * Returns the {@see AggregateRootInterface} identifier.
     * 
     * @return mixed
     */

    public function getAggregateIdentifier()
    {
        return $this->aggregateId;
    }

    /**
     * Returns a list of events 
     * @return array<GenericDomainEventMessage>
     */
    public function getEventList()
    {
        return $this->events;
    }

    /**
     * Adds an {@see EventRegistrationCallbackInterface} to this event container.
     * 
     * @param \Governor\Framework\Domain\EventRegistrationCallbackInterface $eventRegistrationCallback
     */
    public function addEventRegistrationCallback(EventRegistrationCallbackInterface $eventRegistrationCallback)
    {        
        $this->registrationCallbacks[] = $eventRegistrationCallback;

        foreach ($this->events as &$event) {            
            $event = $eventRegistrationCallback->onRegisteredEvent($event);
        }
    }

}
