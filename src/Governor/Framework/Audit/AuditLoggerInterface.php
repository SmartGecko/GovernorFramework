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

namespace Governor\Framework\Audit;

/**
 * Interface describing a component capable of writing auditing entries to a log.
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
interface AuditLoggerInterface
{

    /**
     * Writes a success entry to the audit logs.
     * <p/>
     * This method may be invoked in the thread dispatching the command. Therefore, considering writing asynchronously
     * when the underlying mechanism is slow.
     *
     * @param mixed $command     The command that has been handled
     * @param mixed $returnValue The return value of the command handler
     * @param array $events      The events that were generated during command handling
     */
    public function logSuccessful($command, $returnValue, array $events);

    /**
     * Writes a failure entry to the audit logs. The given list of events may contain events. In that case, these event
     * may have been stored in the events store and/or published to the event bus.
     * <p/>
     * This method may be invoked in the thread dispatching the command. Therefore, considering writing asynchronously
     * when the underlying mechanism is slow.
     *
     * @param mixed $command      The command being executed
     * @param \Exception|null $cause The cause of the rollback. May be <code>null</code> if the rollback was not caused by an
     *                     exception
     * @param array $events       any events staged for storage or publishing
     */
    public function logFailed($command, \Exception $cause = null, array $events);
}
