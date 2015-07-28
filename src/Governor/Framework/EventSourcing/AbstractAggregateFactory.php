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

use Governor\Framework\Domain\DomainEventMessageInterface;

/**
 * Base aggregate factory implementation that creates a new aggregate root from a
 * {@see DomainEventMessageInterface}. The implementation is capable of handling snapshot events.
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
abstract class AbstractAggregateFactory implements AggregateFactoryInterface
{

    /**
     * {@inheritdoc}
     */
    public function createAggregate(
        $aggregateIdentifier,
        DomainEventMessageInterface $firstEvent
    ) {
        if (is_subclass_of(
            $firstEvent->getPayloadType(),
            EventSourcedAggregateRootInterface::class
        )) {
            $aggregate = $firstEvent->getPayload();
        } else {
            $aggregate = $this->doCreateAggregate(
                $aggregateIdentifier,
                $firstEvent
            );
        }

        return $this->postProcessInstance($aggregate);
    }

    /**
     * Perform any processing that must be done on an aggregate instance that was reconstructured from a Snapshot
     * Event. Implementations may choose to modify the existing instance, or return a new instance.
     * <p/>
     * This method can be safely overridden. This implementation does nothing.
     *
     * @param mixed $aggregate The aggregate to post-process.
     * @return mixed The aggregate to initialize with the Event Stream
     */
    protected function postProcessInstance($aggregate)
    {
        return $aggregate;
    }

    /**
     * Create an uninitialized Aggregate instance with the given <code>aggregateIdentifier</code>. The given
     * <code>firstEvent</code> can be used to define the requirements of the aggregate to create.
     * <p/>
     * The given <code>firstEvent</code> is never a snapshot event.
     *
     * @param string $aggregateIdentifier The identifier of the aggregate to create
     * @param DomainEventMessageInterface $firstEvent The first event in the Event Stream of the Aggregate
     * @return mixed The aggregate instance to initialize with the Event Stream
     */
    protected abstract function doCreateAggregate(
        $aggregateIdentifier,
        DomainEventMessageInterface $firstEvent
    );
}
