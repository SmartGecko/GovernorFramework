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

namespace Governor\Framework\CommandHandling;

/**
 * Implementation of the {@see CommandHandlerRegistryInterface} that stores its handlers as an in memory array.
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
class InMemoryCommandHandlerRegistry implements CommandHandlerRegistryInterface
{

    /**
     * @var CommandHandlerInterface[]
     */
    private $subscriptions;

    /**
     *
     */
    function __construct()
    {
        $this->subscriptions = [];
    }


    /**
     * Finds and returns the suitable CommandHandlerInterface for the command message
     * or throws a NoHandlerForCommandException if none exist.
     *
     * @param CommandMessageInterface $message
     * @return CommandHandlerInterface
     * @throws NoHandlerForCommandException
     */
    public function findCommandHandlerFor(CommandMessageInterface $message)
    {
        if (!isset($this->subscriptions[$message->getCommandName()])) {
            throw new NoHandlerForCommandException(
                sprintf(
                    "No handler was subscribed for command [%s]",
                    $message->getCommandName()
                )
            );
        }

        return $this->subscriptions[$message->getCommandName()];
    }

    /**
     * Subscribe the given <code>handler</code> to commands of type <code>commandType</code>.
     * <p/>
     * If a subscription already exists for the given type, the behavior is undefined. Implementations may throw an
     * Exception to refuse duplicate subscription or alternatively decide whether the existing or new
     * <code>handler</code> gets the subscription.
     *
     * @param string $commandName The name of the command to subscribe the handler to
     * @param CommandHandlerInterface $handler The handler service that handles the given type of command
     */
    public function subscribe($commandName, CommandHandlerInterface $handler)
    {
        $this->subscriptions[$commandName] = $handler;
    }

    /**
     * Unsubscribe the given <code>handler</code> to commands of type <code>commandType</code>. If the handler is not
     * currently assigned to that type of command, no action is taken.
     *
     * @param string $commandName The name of the command the handler is subscribed to
     * @param CommandHandlerInterface $handler The handler service to unsubscribe from the CommandBus
     * @return boolean <code>true</code> of this handler is successfully unsubscribed, <code>false</code> of the given
     *         <code>handler</code> was not the current handler for given <code>commandType</code>.
     */
    public function unsubscribe($commandName, CommandHandlerInterface $handler)
    {
        if (isset($this->subscriptions[$commandName])) {
            unset($this->subscriptions[$commandName]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscriptions()
    {
        return $this->subscriptions;
    }
}