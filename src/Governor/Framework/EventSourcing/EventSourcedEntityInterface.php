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
 * 
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a> 
 */
interface EventSourcedEntityInterface
{
     /**
     * Register the aggregate root with this entity. The entity must use this aggregate root to apply Domain Events.
     * The aggregate root is responsible for tracking all applied events.
     * <p/>
     * A parent entity is responsible for invoking this method on its child entities prior to propagating events to it.
     * Typically, this means all entities have their aggregate root set before any actions are taken on it.
     *
     * @param AbstractEventSourcedAggregateRoot $aggregateRootToRegister the root of the aggregate this entity is part of.
     */
    public function registerAggregateRoot(AbstractEventSourcedAggregateRoot $aggregateRootToRegister);

    /**
     * Report the given <code>event</code> for handling in the current instance (<code>this</code>), as well as all the
     * entities referenced by this instance.
     *
     * @param DomainEventMessageInterface $event The event to handle
     */
    public function handleRecursively(DomainEventMessageInterface $event);
}
