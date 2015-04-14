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

use Governor\Framework\Domain\DomainEventStreamInterface;

/**
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a> 
 */
interface EventStreamDecoratorInterface
{

    /**
     * Called when an event stream is read from the event store.
     * <p/>
     * Note that a stream is read-once, similar to InputStream. If you read from the stream, make sure to store the read
     * events and pass them to the chain. Usually, it is best to decorate the given <code>eventStream</code> and pass
     * that to the chain.
     *
     * @param string $aggregateType       The type of aggregate events are being read for
     * @param string $aggregateIdentifier The identifier of the aggregate events are loaded for
     * @param DomainEventStreamInterface $eventStream         The eventStream containing the events to append to the event store  @return The
     *                            decorated event stream
     * @return DomainEventStreamInterface the decorated event stream
     */
    public function decorateForRead($aggregateType, $aggregateIdentifier,
        DomainEventStreamInterface $eventStream);

    /**
     * Called when an event stream is appended to the event store.
     * <p/>
     * Note that a stream is read-once, similar to InputStream. If you read from the stream, make sure to store the read
     * events and pass them to the chain. Usually, it is best to decorate the given <code>eventStream</code> and pass
     * that to the chain.
     *
     * @param string $aggregateType The type of aggregate events are being appended for
     * @param EventSourcedAggregateRootInterface $aggregate     The aggregate for which the events are being stored
     * @param DomainEventStreamInterface $eventStream   The eventStream containing the events to append to the event store  @return The decorated
     *                      event stream
     * @return DomainEventStreamInterface the decorated event stream
     */
    public function decorateForAppend($aggregateType,
        EventSourcedAggregateRootInterface $aggregate,
        DomainEventStreamInterface $eventStream);
}
