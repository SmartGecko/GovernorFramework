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

namespace Governor\Framework\Test\Utils;

use Governor\Framework\CommandHandling\CommandBusInterface;
use Governor\Framework\CommandHandling\CommandCallbackInterface;
use Governor\Framework\CommandHandling\CommandHandlerInterface;
use Governor\Framework\CommandHandling\CommandMessageInterface;


class RecordingCommandBus implements CommandBusInterface
{
    /**
     * @var array
     */
    private $subscriptions;

    /**
     * @var array
     */
    private $dispatchedCommands = [];

    /**
     * @var CallbackBehaviorInterface
     */
    private $callbackBehavior;

    function __construct()
    {
        $this->callbackBehavior = new DefaultCallbackBehavior();
        $this->subscriptions = [];
    }


    public function dispatch(
        CommandMessageInterface $command,
        CommandCallbackInterface $callback = null
    ) {
        $this->dispatchedCommands[] = $command;

        try {
            if (null !== $callback) {
                $callback->onSuccess($this->callbackBehavior->handle($command->getPayload(), $command->getMetaData()));
            }
        } catch (\Exception $ex) {
            if (null !== $callback) {
                $callback->onFailure($ex);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function subscribe($commandName, CommandHandlerInterface $handler)
    {
        if (!array_key_exists($commandName, $this->subscriptions)) {
            $this->subscriptions[$commandName] = $handler;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function unsubscribe($commandName, CommandHandlerInterface $handler)
    {
        if (array_key_exists($commandName, $this->subscriptions)) {
            unset($this->subscriptions[$commandName]);
        }
    }

    /**
     * Clears all the commands recorded by this Command Bus.
     */
    public function clearCommands()
    {
        $this->dispatchedCommands = [];
    }

    /**
     * Clears all subscribed handlers on this command bus.
     */
    public function clearSubscriptions()
    {
        $this->subscriptions = array();
    }

    /**
     * Indicates whether the given <code>commandHandler</code> is subscribed to this command bus.
     *
     * @param CommandHandlerInterface $commandHandler The command handler to verify the subscription for
     * @return boolean <code>true</code> if the handler is subscribed, otherwise <code>false</code>.
     */
    public function isSubscribed(CommandHandlerInterface $commandHandler)
    {
        foreach ($this->subscriptions as $cmd => $handler) {
            if ($commandHandler == $handler) {
                return true;
            }
        }

        return false;
    }


    /**
     * Returns a Map will all Command Names and their Command Handler that have been subscribed to this command bus.
     *
     * @return array a Map will all Command Names and their Command Handler
     */
    public function getSubscriptions()
    {
        return $this->subscriptions;
    }

    /**
     * Returns a list with all commands that have been dispatched by this command bus.
     *
     * @return array a list with all commands that have been dispatched
     */
    public function getDispatchedCommands()
    {
        return $this->dispatchedCommands;
    }


    /**
     * Sets the instance that defines the behavior of the Command Bus when a command is dispatched with a callback.
     *
     * @param CallbackBehaviorInterface $callbackBehavior The instance deciding to how the callback should be invoked.
     */
    public function setCallbackBehavior(CallbackBehaviorInterface $callbackBehavior)
    {
        $this->callbackBehavior = $callbackBehavior;
    }


}