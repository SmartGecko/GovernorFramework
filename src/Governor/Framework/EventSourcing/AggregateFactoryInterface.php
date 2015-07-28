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
 * Interface describing the functionality of the aggregate root factory.
 * The aggregate factory creates an aggregate instance from a stream of domain events.
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
interface AggregateFactoryInterface
{

    /**
     * Instantiate the aggregate using the given aggregate identifier and first event. The first event of the event
     * stream is passed to allow the factory to identify the actual implementation type of the aggregate to create. The
     * first event can be either the event that created the aggregate or, when using event sourcing, a snapshot event.
     * In either case, the event should be designed, such that these events contain enough information to deduct the
     * actual aggregate type.
     *
     * @param string $aggregateIdentifier the aggregate identifier of the aggregate to instantiate
     * @param DomainEventMessageInterface $firstEvent The first event in the event stream. This is either the event generated during
     *                            creation of the aggregate, or a snapshot event
     * @return mixed an aggregate ready for initialization using a DomainEventStream.
     */
    public function createAggregate(
        $aggregateIdentifier,
        DomainEventMessageInterface $firstEvent
    );

    /**
     * Returns the type identifier for this aggregate factory. The type identifier is used by the EventStore to
     * organize data related to the same type of aggregate.
     * <p/>
     * Tip: in most cases, the simple class name would be a good start.
     *
     * @return string the type identifier of the aggregates this repository stores
     */
    public function getTypeIdentifier();

    /**
     * Returns the type of aggregate this factory creates. All instances created by this factory must be an
     * <code>instanceOf</code> this type.
     *
     * @return string The type of aggregate created by this factory
     */
    public function getAggregateType();
}
