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

namespace Governor\Framework\EventHandling;

/**
 * A cluster represents a group of Event Listeners that are treated as a single group by the {@link
 * ClusteringEventBus}. This allows attributes and behavior (e.g. transaction management, asynchronous processing,
 * distribution) to be applied over a whole group at once.
 */
interface ClusterInterface
{

    /**
     * Returns the name of this cluster. This name is used to detect distributed instances of the
     * same cluster. Multiple instances referring to the same logical cluster (on different JVM's) must have the same
     * name.
     *
     * @return string The name of this cluster.
     */
    public function getName();

    /**
     * Publishes the given Events to the members of this cluster.
     * <p/>
     * Implementations may do this synchronously or asynchronously. Although {@link EventListener EventListeners} are
     * discouraged to throw exceptions, it is possible that they are propagated through this method invocation. In that
     * case, no guarantees can be given about the delivery of Events at all Cluster members.
     *
     * @param array $events The Events to publish in the cluster
     */
    public function publish(array $events);

    /**
     * Subscribe the given {@code eventListener} to this cluster. If the listener is already subscribed, nothing
     * happens.
     * <p/>
     * While the Event Listeners is subscribed, it will receive all messages published to the cluster.
     *
     * @param EventListenerInterface $eventListener the Event Listener instance to subscribe
     */
    public function subscribe(EventListenerInterface $eventListener);

    /**
     * Unsubscribes the given {@code eventListener} from this cluster. If the listener is already unsubscribed, or was
     * never subscribed, nothing happens.
     *
     * @param EventListenerInterface $eventListener the Event Listener instance to unsubscribe
     */
    public function unsubscribe(EventListenerInterface $eventListener);

    /**
     * Returns a read-only view on the members in the cluster. This view may be updated by the Cluster when members
     * subscribe or
     * unsubscribe. Cluster implementations may also return the view representing the state at the moment this method
     * is invoked.
     *
     * @return array a view of the members of this cluster
     */
    public function getMembers();

    /**
     * Returns the MetaData of this Cluster.
     *
     * @return the MetaData of this Cluster
     */
    public function getMetaData();
}
