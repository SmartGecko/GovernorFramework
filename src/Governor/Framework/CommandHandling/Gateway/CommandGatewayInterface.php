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

namespace Governor\Framework\CommandHandling\Gateway;

use Governor\Framework\Domain\MetaData;
use Governor\Framework\CommandHandling\CommandCallbackInterface;

/**
 * Command gateway interface definition.
 * 
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a> 
 */
interface CommandGatewayInterface
{

    /**
     * Sends the given <code>command</code>, and have the result of the command's execution reported to the given
     * <code>callback</code>.
     * <p/>
     * The given <code>command</code> is wrapped as the payload of the CommandMessage that is eventually posted on the
     * Command Bus, unless Command already implements {@link org.axonframework.domain.Message}. In that case, a
     * CommandMessage is constructed from that message's payload and MetaData.
     *
     * @param mixed $command  The command to dispatch
     * @param CommandCallbackInterface $callback The callback to notify when the command has been processed     
     */
    public function send($command, CommandCallbackInterface $callback = null);

    /**
     * Sends the given <code>command</code> and wait for it to execute. The result of the execution is returned when
     * available. This method will block indefinitely, until a result is available, or until the Thread is interrupted.
     * When the thread is interrupted, this method returns <code>null</code>. If command execution resulted in an
     * exception, it is wrapped in a {@link org.axonframework.commandhandling.CommandExecutionException}.
     * <p/>
     * The given <code>command</code> is wrapped as the payload of the CommandMessage that is eventually posted on the
     * Command Bus, unless Command already implements {@link org.axonframework.domain.Message}. In that case, a
     * CommandMessage is constructed from that message's payload and MetaData.
     * <p/>
     * Note that the interrupted flag is set back on the thread if it has been interrupted while waiting.
     *
     * @param mixed $command The command to dispatch  
     * @return mixed $the result of command execution.
     */
    public function sendAndWait($command);
}
