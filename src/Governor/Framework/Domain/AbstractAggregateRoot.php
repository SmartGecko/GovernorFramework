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
 * Base implementation of the AggregateRootInterface
 * 
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
abstract class AbstractAggregateRoot implements AggregateRootInterface
{

    /**
     * @var integer
     */
    protected $version;

    /**
     * @var EventContainer
     */
    protected $eventContainer;

    /**
     *
     * @var boolean
     */
    protected $deleted;

    /**
     * @var integer
     */
    protected $lastEventScn;

    /**
     * @param mixed $payload
     * @param MetaData|null $metaData
     * @return GenericDomainEventMessage
     */
    protected function registerEvent($payload, MetaData $metaData = null)
    {
        $meta = (null === $metaData) ? MetaData::emptyInstance() : $metaData;

        return $this->getEventContainer()->addEvent($meta, $payload);
    }

    /**
     * Marks the aggregate root as deleted.
     */
    protected function markDeleted()
    {
        $this->deleted = true;
    }

    /**
     * @inheritdoc
     */
    public function commitEvents()
    {
        if (null !== $this->eventContainer) {
            $this->lastEventScn = $this->eventContainer->getLastScn();
            $this->eventContainer->commit();
        }
    }

    /**
     * @inheritdoc
     */
    public function getUncommittedEventCount()
    {
        return (null === $this->eventContainer) ? 0 : $this->eventContainer->size();
    }

    /**
     * @inheritdoc
     */
    public function getUncommittedEvents()
    {
        if (null === $this->eventContainer) {
            return SimpleDomainEventStream::emptyStream();
        }

        return $this->eventContainer->getEventStream();
    }

    /**
     * @inheritdoc
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @inheritdoc
     */
    public function isDeleted()
    {
        return $this->deleted;
    }

    protected function getLastCommittedEventScn()
    {
        if (null === $this->eventContainer) {
            return $this->lastEventScn;
        }

        return $this->eventContainer->getLastCommitedScn();
    }

    /**
     * @return EventContainer
     */
    private function getEventContainer()
    {
        if (null === $this->eventContainer) {
            if (null === $this->getIdentifier()) {
                throw new AggregateRootIdNotInitialized("Aggregate Id unknown in [" .
                get_class($this) .
                "] Make sure the Aggregate Id is initialized before registering events.");
            }

            $this->eventContainer = new EventContainer($this->getIdentifier());
            $this->eventContainer->initializeSequenceNumber($this->lastEventScn);
        }

        return $this->eventContainer;
    }

    protected function initializeEventStream($lastScn)
    {
        $this->getEventContainer()->initializeSequenceNumber($lastScn);
        $this->lastEventScn = $lastScn >= 0 ? $lastScn : null;
    }

    public function addEventRegistrationCallback(EventRegistrationCallbackInterface $callback)
    {
        $this->getEventContainer()->addEventRegistrationCallback($callback);
    }
}
