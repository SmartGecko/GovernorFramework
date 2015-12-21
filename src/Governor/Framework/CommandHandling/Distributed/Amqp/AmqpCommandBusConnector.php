<?php
/**
 * This file is part of the SmartGecko(c) business platform.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Governor\Framework\CommandHandling\Distributed\Amqp;

use Governor\Framework\Cluster\ClusterInterface;
use Governor\Framework\CommandHandling\Distributed\CommandDispatchException;
use Governor\Framework\CommandHandling\Distributed\DispatchMessage;
use Governor\Framework\CommandHandling\Distributed\ReplyMessage;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Channel\AMQPChannel;
use Governor\Framework\CommandHandling\NoHandlerForCommandException;
use Governor\Framework\CommandHandling\CommandCallbackInterface;
use Governor\Framework\CommandHandling\CommandHandlerInterface;
use Governor\Framework\CommandHandling\CommandMessageInterface;
use Governor\Framework\CommandHandling\Distributed\CommandBusConnectorInterface;
use Governor\Framework\Serializer\MessageSerializer;
use Governor\Framework\Serializer\SerializerInterface;
use Governor\Framework\CommandHandling\CommandBusInterface;
use PhpAmqpLib\Message\AMQPMessage;

class AmqpCommandBusConnector implements CommandBusConnectorInterface
{
    const HANDLERS_NODE = 'handlers';

    /**
     * @var AMQPStreamConnection
     */
    private $connection;

    /**
     * @var ClusterInterface
     */
    private $cluster;

    /**
     * @var AMQPChannel
     */
    private $channel;

    /**
     * @var CommandBusInterface
     */
    private $localSegment;

    /**
     * @var MessageSerializer
     */
    private $serializer;

    /**
     * AmqpCommandBusConnector constructor.
     *
     * @param AMQPStreamConnection $connection
     * @param ClusterInterface $cluster
     * @param CommandBusInterface $localSegment
     * @param SerializerInterface $serializer
     */
    public function __construct(
        AMQPStreamConnection $connection,
        ClusterInterface $cluster,
        CommandBusInterface $localSegment,
        SerializerInterface $serializer
    ) {
        $this->connection = $connection;
        $this->localSegment = $localSegment;
        $this->cluster = $cluster;
        $this->serializer = new MessageSerializer($serializer);

        $this->channel = $this->connection->channel();
    }

    private function onResponse(AMQPMessage $response)
    {

    }


    /**
     * @inheritDoc
     */
    public function send($routingKey, CommandMessageInterface $command, CommandCallbackInterface $callback = null)
    {
        $expectReply = isset($callback) ? true : false;

        /** @var ReplyMessage $replyMessage */
        $replyMessage = null;

        $messageCallback = function (AMQPMessage $response) use (&$replyMessage, $command) {
            if ($response->get('correlation_id') === $command->getIdentifier()) {
                $replyMessage = ReplyMessage::fromBytes($this->serializer, $response->body);
                var_dump($replyMessage->getError());
            }
        };

        try {
            $destination = $this->findSuitableNode($command);

            list($callbackQueue, ,) = $this->channel->queue_declare('', false, false, true, false);
            $this->channel->basic_consume($callbackQueue, '', false, false, false, false, \Closure::bind($messageCallback, $this));

            $dispatchMessage = new DispatchMessage($command, $this->serializer, $expectReply);

            $amqpMessageOptions = $expectReply ? [
                'correlation_id' => $command->getIdentifier(),
                'reply_to' => $callbackQueue
            ] : [];

            $amqpMessage = new AMQPMessage($dispatchMessage->toBytes(), $amqpMessageOptions);
            $this->channel->basic_publish($amqpMessage, '', $destination);

            if ($expectReply) {
                while (!$replyMessage) {
                    $this->channel->wait();
                }

                if ($replyMessage->isSuccess()) {
                    $callback->onSuccess($replyMessage->getReturnValue());
                } else {
                    $callback->onFailure(new CommandDispatchException($replyMessage->getError()));
                }

            }

        } catch (\Exception $e) {
            if ($callback) {
                $callback->onFailure($e);
            }
        }
    }

    /**
     * @param CommandMessageInterface $command
     * @return string
     */
    private function findSuitableNode(CommandMessageInterface $command)
    {
        $registry = $this->cluster->createMessageRegistry(self::HANDLERS_NODE);
        $destination = $registry->getRoutingPolicy()->getDestinationNode($command->getPayloadType());

        if (null === $destination) {
            throw new NoHandlerForCommandException(
                sprintf(
                    "No handler in cluster was subscribed for command [%s]",
                    $command->getCommandName()
                )
            );
        }

        return $destination;
    }

    /**
     * @inheritDoc
     */
    public function subscribe($commandName, CommandHandlerInterface $handler)
    {
        $this->localSegment->subscribe($commandName, $handler);
    }

    /**
     * @inheritDoc
     */
    public function unsubscribe($commandName, CommandHandlerInterface $handler)
    {
        $this->localSegment->unsubscribe($commandName, $handler);
    }

}