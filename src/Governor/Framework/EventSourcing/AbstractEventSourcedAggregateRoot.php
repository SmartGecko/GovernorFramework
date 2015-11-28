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

namespace Governor\Framework\EventSourcing;

use Governor\Framework\Domain\MetaData;
use Governor\Framework\Domain\GenericDomainEventMessage;
use Governor\Framework\Domain\DomainEventMessageInterface;
use Governor\Framework\Domain\DomainEventStreamInterface;
use Governor\Framework\Domain\AbstractAggregateRoot;

/**
 * Abstract implementation of the {@see EventSourcedAggregateRootInterface} to be used as a base class for event sourced aggregates.
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
abstract class AbstractEventSourcedAggregateRoot extends AbstractAggregateRoot implements EventSourcedAggregateRootInterface
{

    /**
     * {@inheritdoc}
     */
    public function initializeState(DomainEventStreamInterface $domainEventStream)
    {
        if (0 !== $this->getUncommittedEventCount()) {
            throw new \RuntimeException("Aggregate is already initialized");
        }

        $lastScn = -1;

        while ($domainEventStream->hasNext()) {
            $event = $domainEventStream->next();
            $lastScn = $event->getScn();
            $this->handleRecursively($event);
        }

        $this->initializeEventStream($lastScn);
    }

    /**
     * @return EventSourcedEntityInterface[]
     */
    abstract protected function getChildEntities();

    /**
     * @param DomainEventMessageInterface $event
     * @return mixed
     */
    abstract protected function handle(DomainEventMessageInterface $event);

    /**
     * @param mixed $payload
     * @param MetaData $metaData
     */
    public function apply($payload, MetaData $metaData = null)
    {
        $metaData = isset($metaData) ? $metaData : MetaData::emptyInstance();

        if (null === $this->getIdentifier()) {
            if ($this->getUncommittedEventCount() > 0 || $this->getVersion() !== null) {
                throw new \RuntimeException(
                    "The Aggregate Identifier has not been initialized. "
                    ."It must be initialized at the latest when the "
                    ."first event is applied."
                );
            }
            $this->handleRecursively(
                new GenericDomainEventMessage(
                    null, 0,
                    $payload, $metaData
                )
            );
            $this->registerEvent($payload, $metaData);
        } else {
            $event = $this->registerEvent($payload, $metaData);
            $this->handleRecursively($event);
        }
    }

    private function handleRecursively(DomainEventMessageInterface $event)
    {
        $this->handle($event);

        if (null === $childEntities = $this->getChildEntities()) {
            return;
        }

        foreach ($childEntities as $child) {
            if (null !== $child) {
                $child->registerAggregateRoot($this);
                $child->handleRecursively($event);
            }
        }
    }


    /**
     * @return int
     */
    public function getVersion()
    {
        return $this->getLastCommittedEventScn();
    }

}
