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

namespace Governor\Framework\CommandHandling\Distributed;

use Governor\Framework\CommandHandling\NoHandlerForCommandException;
use Governor\Framework\Serializer\MessageSerializer;
use Governor\Framework\CommandHandling\CommandBusInterface;
use Governor\Framework\Serializer\SerializerInterface;
use Governor\Framework\CommandHandling\CommandCallbackInterface;
use Governor\Framework\CommandHandling\CommandHandlerInterface;
use Governor\Framework\CommandHandling\CommandMessageInterface;

class RedisCommandBusConnector implements CommandBusConnectorInterface
{

    /**
     * @var RedisTemplate
     */
    private $template;

    /**
     * @var CommandBusInterface
     */
    private $localSegment;

    /**
     * @var MessageSerializer
     */
    private $serializer;

    /**
     * @param RedisTemplate $template
     * @param CommandBusInterface $localSegment
     * @param SerializerInterface $serializer
     */
    function __construct(RedisTemplate $template, CommandBusInterface $localSegment, SerializerInterface $serializer)
    {
        $this->template = $template;
        $this->localSegment = $localSegment;
        $this->serializer = new MessageSerializer($serializer);
    }


    /**
     * {@inheritdoc}
     */
    public function send($routingKey, CommandMessageInterface $command, CommandCallbackInterface $callback = null)
    {
        $destination = $this->template->getRoutingDestination($command->getCommandName(), $routingKey);

        if (null === $destination) {
            $destination = $this->findSuitableNode($command);
            $this->template->setRoutingDestination($destination, $command->getCommandName(), $routingKey);
        }

        // dispatch locally if destination matches this node
        if ($this->template->getNodeName() === $destination) {
            $this->localSegment->dispatch($command, $callback);
            return;
        }

        $awaitReply = $callback ? true : false;
        $dispatchMessage = new DispatchMessage($command, $this->serializer, $awaitReply);

        $this->template->enqueueCommand($destination, $dispatchMessage->toBytes());

        if ($awaitReply) {
            $reply = $this->template->readCommandReply($command->getIdentifier());

            if (null === $reply) {
                $callback->onFailure(new CommandTimeoutException($command->getIdentifier()));
                return;
            }

            $replyMessage = ReplyMessage::fromBytes($this->serializer, $reply[1]);

            if ($replyMessage->isSuccess()) {
                $callback->onSuccess($replyMessage->getReturnValue());
            } else {
                $callback->onFailure(new CommandDispatchException($replyMessage->getError()));
            }
        }
    }

    private function findSuitableNode(CommandMessageInterface $command)
    {
        $nodes = $this->template->getSubscriptions($command->getCommandName());

        if (empty($nodes)) {
            throw new NoHandlerForCommandException(
                sprintf(
                    "No handler in cluster was subscribed for command [%s]",
                    $command->getCommandName()
                )
            );
        }

        return $nodes[0]; // TODO temporary something more elaborate :)
    }

    /**
     * Subscribe the given <code>handler</code> to commands of type <code>commandType</code> to the local segment of the
     * command bus.
     * <p/>
     * If a subscription already exists for the given type, the behavior is undefined. Implementations may throw an
     * Exception to refuse duplicate subscription or alternatively decide whether the existing or new
     * <code>handler</code> gets the subscription.
     *
     * @param string $commandName The name of the command to subscribe the handler to
     * @param CommandHandlerInterface $handler The handler instance that handles the given type of command
     */
    public function subscribe($commandName, CommandHandlerInterface $handler)
    {
        $this->localSegment->subscribe($commandName, $handler);
    }

    /**
     * Unsubscribe the given <code>handler</code> to commands of type <code>commandType</code>. If the handler is not
     * currently assigned to that type of command, no action is taken.
     *
     * @param string $commandName The name of the command the handler is subscribed to
     * @param CommandHandlerInterface $handler The handler instance to unsubscribe from the CommandBus
     */
    public function unsubscribe($commandName, CommandHandlerInterface $handler)
    {
        $this->localSegment->unsubscribe($commandName, $handler);
    }

    /**
     *
     */
    public function saveSubscriptions()
    {
        foreach ($this->localSegment->getSubscriptions() as $command => $handler) {
            $this->template->subscribe($command);
        }
    }

    /**
     *
     */
    public function clearSubscriptions()
    {
        foreach ($this->localSegment->getSubscriptions() as $command => $handler) {
            $this->template->unsubscribe($command);
        }
    }

    /**
     * @return string
     */
    public function getNodeName()
    {
        return $this->template->getNodeName();
    }

}