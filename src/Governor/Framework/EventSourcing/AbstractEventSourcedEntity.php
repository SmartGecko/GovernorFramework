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
use Governor\Framework\Domain\DomainEventMessageInterface;

/**
 * Description of AbstractEventSourcedEntity
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
abstract class AbstractEventSourcedEntity implements EventSourcedEntityInterface
{
    /**
     * @var AbstractEventSourcedAggregateRoot
     */
    private $aggregateRoot;

    /**
     * {@inheritdoc}
     */
    public function handleRecursively(DomainEventMessageInterface $event)
    {
        $this->handle($event);

        if (null === $childEntities = $this->getChildEntities()) {
            return;
        }

        foreach ($childEntities as $child) {
            if (null !== $child) {
                $child->registerAggregateRoot($this->aggregateRoot);
                $child->handleRecursively($event);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function registerAggregateRoot(AbstractEventSourcedAggregateRoot $aggregateRootToRegister)
    {
        if (null !== $this->aggregateRoot && $this->aggregateRoot !== $aggregateRootToRegister) {
            throw new \RuntimeException(
                "Cannot register new aggregate. "
                ."This entity is already part of another aggregate"
            );
        }

        $this->aggregateRoot = $aggregateRootToRegister;
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
     * @param mixed $event
     * @param MetaData $metaData
     */
    public function apply($event, MetaData $metaData = null)
    {
        if (null === $this->aggregateRoot) {
            throw new \RuntimeException(
                "The aggregate root is unknown. "
                ."Is this entity properly registered as the child of an aggregate member?"
            );
        }

        $this->aggregateRoot->apply($event, $metaData);
    }

    /**
     * @return AbstractEventSourcedAggregateRoot
     */
    protected function getAggregateRoot()
    {
        return $this->aggregateRoot;
    }

}
