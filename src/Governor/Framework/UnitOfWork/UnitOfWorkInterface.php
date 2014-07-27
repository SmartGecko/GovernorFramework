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

use Governor\Framework\Domain\AggregateRootInterface;
use Governor\Framework\Domain\EventMessageInterface;
use Governor\Framework\EventHandling\EventBusInterface;

/**
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a> 
 */
interface UnitOfWorkInterface
{

    public function commit();

    public function rollback(\Exception $ex = null);

    public function start();

    public function registerListener($listener);

    /**
     * Indicates whether this UnitOfWork is started. It is started when the {@link #start()} method has been called,
     * and
     * if the UnitOfWork has not been committed or rolled back.
     *
     * @return boolean <code>true</code> if this UnitOfWork is started, <code>false</code> otherwise.
     */
    public function isStarted();

    /**
     * Indicates whether this UnitOfWork is bound to a transaction.
     *
     * @return boolean <code>true</code> if this unit of work is bound to a transaction, otherwise <code>false</code>
     */
    public function isTransactional();

    public function registerAggregate(AggregateRootInterface $aggregateRoot,
        EventBusInterface $eventBus,
        SaveAggregateCallbackInterface $saveAggregateCallback);

    /**
     * Request to publish the given <code>event</code> on the given <code>eventBus</code>. The UnitOfWork may either
     * publish immediately, or buffer the events until the UnitOfWork is committed.
     *
     * @param EventMessageInterface $event    The event to be published on the event bus
     * @param EventBusInterface $eventBus The event bus on which to publish the event
     */
    public function publishEvent(EventMessageInterface $event,
        EventBusInterface $eventBus);
}
