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

namespace Governor\Framework\EventStore;

use Psr\Log\LoggerAwareInterface;
use Governor\Framework\Domain\DomainEventStreamInterface;

/**
 * Stores events grouped together in streams identified by their identifier.
 *
 * The EventStore is used to implement EventSourcing in GovernorFramework
 * and is not neeeded otherwise.
 * 
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a> 
 */
interface EventStoreInterface extends LoggerAwareInterface
{

    /**
     * Append the events in the given {@link DomainEventStreamInterface stream} to the event store.
     *
     * @param string $type   The type descriptor of the object to store
     * @param DomainEventStreamInterface $events The event stream containing the events to store
     * @throws EventStoreException if an error occurs while storing the events in the event stream
     */
    public function appendEvents($type, DomainEventStreamInterface $events);

    /**
     * Read the events of the aggregate identified by the given type and identifier that allow the current aggregate
     * state to be rebuilt. Implementations may omit or replace events (e.g. by using snapshot events) from the stream
     * for performance purposes.
     *
     * @param string $type       The type descriptor of the object to retrieve
     * @param mixed $identifier The unique aggregate identifier of the events to load
     * @return DomainEventStreamInterface an event stream containing the events of the aggregate
     *
     * @throws EventStoreException if an error occurs while reading the events in the event stream
     */
    public function readEvents($type, $identifier);
}
