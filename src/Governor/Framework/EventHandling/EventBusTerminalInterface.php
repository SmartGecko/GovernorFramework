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
 * Interface describing a mechanism that connects Event Bus clusters. The terminal is responsible for delivering
 * published Events with all of the clusters available in the Event Bus (either locally, or remotely).
 * <p/>
 * Terminals are typically bound to a single Event Bus instance, but may be aware that multiple instances exist in
 * order to form a bridge between these Event Buses.
 */
interface EventBusTerminalInterface
{

    /**
     * Publishes the given <code>events</code> to all clusters on the Event Bus. The terminal is responsible for the
     * delivery process, albeit local or remote.
     *
     * @param array $events the collections of events to publish
     */
    public function publish(array $events);

    /**
     * Invoked when an Event Listener has been assigned to a cluster that was not yet known to the Event Bus. This
     * method is invoked only once for each cluster that was assigned an Event Listener. Subsequent Event Listeners
     * are added to the cluster. Cluster remain "live" when all event listeners have been removed from them.
     *
     * @param ClusterInterface $cluster the newly created cluster
     */
    public function onClusterCreated(ClusterInterface $cluster);
}
