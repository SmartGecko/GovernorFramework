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

namespace Governor\Framework\UnitOfWork;

use Governor\Framework\Domain\EventMessageInterface;

/**
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
interface UnitOfWorkListenerInterface
{

    /**
     * Invoked when the UnitOfWork is committed. The aggregate has been saved and the events have been scheduled for
     * dispatching. In some cases, the events could already have been dispatched. When processing of this method causes
     * an exception, a UnitOfWork may choose to call {@link #onRollback(UnitOfWork, Throwable)} consecutively.
     *
     * @param UnitOfWorkInterface $unitOfWork The Unit of Work being committed
     */
    public function afterCommit(UnitOfWorkInterface $unitOfWork);

    /**
     * Invoked when the UnitOfWork is rolled back. The UnitOfWork may choose to invoke this method when committing the
     * UnitOfWork failed, too.
     *
     * @param UnitOfWorkInterface $unitOfWork The Unit of Work being rolled back
     * @param \Exception $failureCause The exception (or error) causing the roll back
     */
    public function onRollback(
        UnitOfWorkInterface $unitOfWork,
        \Exception $failureCause = null
    );

    /**
     * Invoked when an Event is registered for publication when the UnitOfWork is committed. Listeners may alter Event
     * information by returning a new instance for the event. Note that the Listener must ensure the functional meaning
     * of the EventMessage does not change. Typically, this is done by only modifying the MetaData on an Event.
     * <p/>
     * The simplest implementation simply returns the given <code>event</code>.
     *
     * @param UnitOfWorkInterface $unitOfWork The Unit of Work on which an event is registered
     * @param EventMessageInterface $event The event about to be registered for publication
     * @return EventMessageInterface the (modified) event to register for publication
     */
    public function onEventRegistered(
        UnitOfWorkInterface $unitOfWork,
        EventMessageInterface $event
    );

    /**
     * Invoked before aggregates are committed, and before any events are published. This phase can be used to do
     * validation or other activity that should be able to prevent event dispatching in certain circumstances.
     * <p/>
     * Note that the given <code>events</code> may not contain the uncommitted domain events of each of the
     * <code>aggregateRoots</code>. To retrieve all events, collect all uncommitted events from the aggregate roots and
     * combine them with the list of events.
     *
     * @param UnitOfWorkInterface $unitOfWork The Unit of Work being committed
     * @param array $aggregateRoots the aggregate roots being committed
     * @param array $events Events that have been registered for dispatching with the UnitOfWork
     */
    public function onPrepareCommit(
        UnitOfWorkInterface $unitOfWork,
        array $aggregateRoots,
        array $events
    );

    /**
     * Invoked before the transaction bound to this Unit of Work is committed, but after all other commit activities
     * (publication of events and saving of aggregates) are performed. This gives resource manager the opportunity to
     * take actions that must be part of the same transaction.
     * <p/>
     * Note that this method is only invoked if the Unit of Work is bound to a transaction.
     *
     * @param UnitOfWorkInterface $unitOfWork The Unit of Work of which the underlying transaction is being committed.
     * @param mixed $transaction The object representing the (status of) the transaction
     */
    public function onPrepareTransactionCommit(
        UnitOfWorkInterface $unitOfWork,
        $transaction
    );

    /**
     * Notifies listeners that the UnitOfWork is being cleaned up. This gives listeners the opportunity to clean up
     * resources that might have been used during commit or rollback, such as remaining locks, open files, etc.
     * <p/>
     * This method is always called after all listeners have been notified of a commit or rollback.
     *
     * @param UnitOfWorkInterface $unitOfWork The Unit of Work being cleaned up
     */
    public function onCleanup(UnitOfWorkInterface $unitOfWork);
}
