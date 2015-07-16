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
use Predis\Client;
use Governor\Framework\CommandHandling\CommandBusInterface;
use Governor\Framework\Serializer\SerializerInterface;
use Governor\Framework\CommandHandling\CommandCallbackInterface;
use Governor\Framework\CommandHandling\CommandHandlerInterface;
use Governor\Framework\CommandHandling\CommandMessageInterface;

class RedisCommandBusConnector implements CommandBusConnectorInterface
{

    /**
     * @var Client
     */
    private $client;

    /**
     * @var CommandBusInterface
     */
    private $localSegment;

    /**
     * @var MessageSerializer
     */
    private $serializer;

    /**
     * @var string
     */
    private $nodeName;

    /**
     * @param Client $client
     * @param CommandBusInterface $localSegment
     * @param SerializerInterface $serializer
     * @param string $nodeName
     */
    function __construct(Client $client, CommandBusInterface $localSegment, SerializerInterface $serializer, $nodeName)
    {
        $this->client = $client;
        $this->localSegment = $localSegment;
        $this->serializer = new MessageSerializer($serializer);
        $this->nodeName = $nodeName;
    }


    /**
     * Sends the given <code>command</code> to the node assigned to handle messages with the given
     * <code>routingKey</code>. The sender expect a reply, and will be notified of the result in the given
     * <code>callback</code>.
     * <p/>
     * If this method throws an exception, the sender is guaranteed that the destination of the command did not receive
     * it. If the method returns normally, the actual implementation of the connector defines the delivery guarantees.
     * Implementations <em>should</em> always invoke the callback with an outcome.
     * <p/>
     * If a member's connection was lost, and the result of the command is unclear, the {@link
     * CommandCallback#onFailure(Throwable)} method is invoked with a {@link RemoteCommandHandlingException} describing
     * the failed connection. A client may choose to resend a command.
     * <p/>
     * Connectors route the commands based on the given <code>routingKey</code>. Using the same <code>routingKey</code>
     * will result in the command being sent to the same member.
     *
     * @param string $routingKey The key describing the routing requirements of this command. Generally, commands with the same
     *                   routingKey will be sent to the same destination.
     * @param CommandMessageInterface $command The command to send to the (remote) member
     * @param CommandCallbackInterface $callback The callback on which result notifications are sent
     * @return mixed
     * @throws \Exception when an error occurs before or during the sending of the message
     */
    public function send($routingKey, CommandMessageInterface $command, CommandCallbackInterface $callback)
    {
        $hash = hash('md5', sprintf('%s-%s', $command->getCommandName(), $routingKey));
        $destination = $this->client->hget('governor:command:routing', $hash);

        if (null === $destination) {
            $destination = $this->findSuitableNode($command);
        }

        $serialized = $this->serializer->serialize($command);
        $this->client->rpush(sprintf('governor:command:%s:request', $destination), [$serialized]);
    }

    private function findSuitableNode(CommandMessageInterface $command)
    {
        $hash = hash('md5', $command->getCommandName());
        $nodes = $this->client->smembers(sprintf('governor:command:subscription:%s', $hash));

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

}