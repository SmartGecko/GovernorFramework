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

/**
 * Interface towards a mechanism that manages transactions
 * <p/>
 * Typically, this will involve opening database transactions or connecting to external systems.
 * 
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a> 
 */
interface TransactionManagerInterface
{
    /**
     * Starts a transaction. The return value is an object representing the transaction status and must be passed as an
     * argument when invoking {@link #commitTransaction(Object)} or {@link #rollbackTransaction(Object)}.
     * <p/>
     * The returned object must never be <code>null</code> if a transaction was successfully created.
     *
     * @return mixed The object representing the transaction status
     */
    public function startTransaction();

    /**
     * Commits the transaction identifier by given <code>transactionStatus</code>.
     *
     * @param mixed $transactionStatus The status object provided by {@link #startTransaction()}.
     */
    public function commitTransaction($transactionStatus);

    /**
     * Rolls back the transaction identifier by given <code>transactionStatus</code>.
     *
     * @param mixed $transactionStatus The status object provided by {@link #startTransaction()}.
     */
    public function rollbackTransaction($transactionStatus);
}
