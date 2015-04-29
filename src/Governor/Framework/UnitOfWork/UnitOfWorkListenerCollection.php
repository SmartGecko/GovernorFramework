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
 * Description of UnitOfWorkListenerCollection
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
class UnitOfWorkListenerCollection implements UnitOfWorkListenerInterface
{

    /**
     * @var UnitOfWorkListenerInterface[]
     */
    private $listeners = [];

    /**
     * {@inheritdoc}
     */
    public function afterCommit(UnitOfWorkInterface $unitOfWork)
    {
        foreach (array_reverse($this->listeners) as $listener) {
            /** @var  UnitOfWorkListenerInterface $listener */
            $listener->afterCommit($unitOfWork);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function onCleanup(UnitOfWorkInterface $unitOfWork)
    {
        foreach (array_reverse($this->listeners) as $listener) {
            /** @var  UnitOfWorkListenerInterface $listener */
            try {
                $listener->onCleanup($unitOfWork);
            } catch (\Exception $ex) {

            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function onEventRegistered(
        UnitOfWorkInterface $unitOfWork,
        EventMessageInterface $event
    ) {
        $newEvent = $event;
        foreach ($this->listeners as $listener) {
            $newEvent = $listener->onEventRegistered($unitOfWork, $newEvent);
        }

        return $newEvent;
    }

    /**
     * {@inheritdoc}
     */
    public function onPrepareCommit(
        UnitOfWorkInterface $unitOfWork,
        array $aggregateRoots,
        array $events
    ) {
        foreach ($this->listeners as $listener) {
            $listener->onPrepareCommit($unitOfWork, $aggregateRoots, $events);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function onPrepareTransactionCommit(
        UnitOfWorkInterface $unitOfWork,
        $transaction
    ) {
        foreach ($this->listeners as $listener) {
            $listener->onPrepareTransactionCommit($unitOfWork, $transaction);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function onRollback(
        UnitOfWorkInterface $unitOfWork,
        \Exception $failureCause = null
    ) {
        foreach (array_reverse($this->listeners) as $listener) {
            /** @var  UnitOfWorkListenerInterface $listener */
            $listener->onRollback($unitOfWork, $failureCause);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function add(UnitOfWorkListenerInterface $listener)
    {
        $this->listeners[] = $listener;
    }

}
