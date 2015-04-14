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

namespace Governor\Framework\Repository;

use Governor\Framework\Domain\AggregateRootInterface;

/**
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
interface LockManagerInterface
{

    /**
     * Make sure that the current thread holds a valid lock for the given aggregate.
     *
     * @param AggregateRootInterface $aggregate the aggregate to validate the lock for
     * @return boolean true if a valid lock is held, false otherwise
     */
    public function validateLock(AggregateRootInterface $aggregate);

    /**
     * Obtain a lock for an aggregate with the given <code>aggregateIdentifier</code>. Depending on the strategy, this
     * method may return immediately or block until a lock is held.
     *
     * @param string $aggregateIdentifier the identifier of the aggregate to obtains a lock for.
     */
    public function obtainLock($aggregateIdentifier);

    /**
     * Release the lock held for an aggregate with the given <code>aggregateIdentifier</code>. The caller of this
     * method must ensure a valid lock was requested using {@link #obtainLock(Object)}. If no lock was successfully
     * obtained, the behavior of this method is undefined.
     *
     * @param string $aggregateIdentifier the identifier of the aggregate to release the lock for.
     */
    public function releaseLock($aggregateIdentifier);
}
