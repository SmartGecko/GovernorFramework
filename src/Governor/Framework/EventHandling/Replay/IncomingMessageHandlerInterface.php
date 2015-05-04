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

namespace Governor\Framework\EventHandling\Replay;

use Governor\Framework\Domain\DomainEventMessageInterface;
use Governor\Framework\EventHandling\EventBusInterface;

/**
 * Interface of a mechanism that receives Messages dispatched to a Cluster that is in Replay mode. The implementation
 * defines if, how and when the cluster should handle events while a replay is in progress.
 * <p/>
 * When replying is finished, the handler is asked to flush any backlog it may have gathered during the replay.
 * <p/>
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
interface IncomingMessageHandlerInterface
{

    /**
     * Invoked just before replay mode is activated. Any messages passed to
     * {@link #onIncomingMessages(org.axonframework.eventhandling.Cluster, org.axonframework.domain.EventMessage[])}
     * prior to this method invocation should be dispatched immediately to the destination cluster to prevent message
     * loss.
     * <p/>
     * This method is invoked in the thread that executes the replay process.
     *
     * @param EventBusInterface $destination The cluster on which events are about te be replayed
     */
    public function prepareForReplay(EventBusInterface $destination);

    /**
     * Invoked while the ReplayingEventBus is in replay mode and an Event is being dispatched to the Cluster. If the
     * timestamp of the given <code>message</code> is before the timestamp of any message reported via {@link
     * #releaseMessage(org.axonframework.eventhandling.Cluster, org.axonframework.domain.DomainEventMessage)}, consider
     * discarding the incoming message.
     * <p/>
     * This method returns the list of messages that must be considered as handled. May be <code>null</code> to
     * indicate all given <code>messages</code> have been stored for processing later.
     * <p/>
     * This method is invoked in the thread that attempts to publish the given messages to the given destination.
     *
     * @param EventBusInterface $destination The cluster to receive the message
     * @param array $messages The messages to dispatch to the cluster
     * @return array a list of messages that may be considered as handled
     */
    public function onIncomingMessages(
        EventBusInterface $destination,
        array $messages
    );

    /**
     * Invoked when a message has been replayed from the event store. If such a message has been received with {@link
     * #onIncomingMessages(org.axonframework.eventhandling.Cluster, org.axonframework.domain.EventMessage[])}, it
     * should be discarded.
     * <p/>
     * After this invocation, any invocation of {@link #onIncomingMessages(org.axonframework.eventhandling.Cluster,
     * org.axonframework.domain.EventMessage[])} with a message who's timestamp (minus a safety buffer to account for
     * clock differences) is lower that this message's timestamp can be safely discarded. It is recommended that
     * non-Domain EventMessages in the backlog are forwarded to the cluster provided, instead of discarded. They must
     * then also be included in the returned list.
     * <p/>
     * This method returns the list of EventMessages that must be considered processed, regardless of whether they have
     * been forwarded to the original <code>destination</code> or not. These EventMessages have been registered in a
     * call to {@link #onIncomingMessages(org.axonframework.eventhandling.Cluster, org.axonframework.domain.EventMessage[])}.
     * <p/>
     * It is highly recommended to return the instance used in the {@link #onIncomingMessages(org.axonframework.eventhandling.Cluster,
     * org.axonframework.domain.EventMessage[])} invocation, over the given <code>message</code>, even if they refer to
     * the save Event.
     * <p/>
     * This method is invoked in the thread that executes the replay process
     *
     * @param EventBusInterface $destination The original destination of the message to be released
     * @param DomainEventMessageInterface $message The message replayed from the event store
     * @return array The list of messages that have been released
     */
    public function releaseMessage(
        EventBusInterface $destination,
        DomainEventMessageInterface $message
    );

    /**
     * Invoked when all events from the Event Store have been processed. Any remaining backlog, as well as any messages
     * received through {@link #onIncomingMessages(org.axonframework.eventhandling.Cluster,
     * org.axonframework.domain.EventMessage[])} should be dispatched to the given <code>delegate</code>. Transactions
     * started by the replay process have been committed or rolled back prior to the invocation of this method.
     * <p/>
     * Note that {@link #onIncomingMessages(org.axonframework.eventhandling.Cluster,
     * org.axonframework.domain.EventMessage[])} may be invoked during or after the invocation of this method. These
     * messages <em>must</em> be dispatched by this handler to prevent message loss.
     * <p/>
     * This method is invoked in the thread that executes the replay process
     *
     * @param EventBusInterface $destination The destination cluster to dispatch backlogged messages to
     */
    public function processBacklog(EventBusInterface $destination);

    /**
     * Invoked when a replay has failed. Typically, this means the state of the cluster's backing data source cannot be
     * guaranteed, and the replay should be retried.
     *
     * @param EventBusInterface $destination The destination cluster to dispatch backlogged messages to, if appropriate in this scenario
     * @param \Exception $cause The cause of the failure
     */
    public function onReplayFailed(
        EventBusInterface $destination,
        \Exception $cause
    );
}
