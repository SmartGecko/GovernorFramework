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

namespace Governor\Framework\CommandHandling\Distributed\Amqp;

use Governor\Framework\Cluster\ClusterInterface;
use Governor\Framework\Cluster\ClusterMessageRegistryInterface;
use Governor\Framework\CommandHandling\Callbacks\ResultCallback;
use Governor\Framework\CommandHandling\Distributed\DispatchMessage;
use Governor\Framework\Cluster\Worker\AbstractAmqpWorker;
use Governor\Framework\CommandHandling\CommandBusInterface;
use Governor\Framework\CommandHandling\Distributed\ReplyMessage;
use Governor\Framework\Serializer\SerializerInterface;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class AmqpCommandHandlerWorker extends AbstractAmqpWorker
{

    const HANDLERS_NODE = 'handlers';

    /**
     * @var CommandBusInterface
     */
    private $commandBus;

    /**
     * @var ClusterMessageRegistryInterface
     */
    private $clusterRegistry;

    /**
     * @param string $nodeIdentifier
     * @param AMQPStreamConnection $connection
     * @param SerializerInterface $serializer
     * @param CommandBusInterface $commandBus
     */
    public function __construct(
        $nodeIdentifier,
        AMQPStreamConnection $connection,
        SerializerInterface $serializer,
        CommandBusInterface $commandBus
    ) {
        parent::__construct($nodeIdentifier, $connection, $serializer);
        $this->commandBus = $commandBus;
    }

    /**
     * @inheritdoc
     */
    protected function onClusterJoined(ClusterInterface $cluster, $sequence)
    {
        $this->clusterRegistry = $cluster->createMessageRegistry(self::HANDLERS_NODE);

        foreach ($this->commandBus->getSubscriptions() as $message => $handler) {
            $this->clusterRegistry->registerMessage($this, $message);
        }
    }

    /**
     * @inheritdoc
     */
    protected function onClusterLeft(ClusterInterface $cluster, $sequence)
    {
        foreach ($this->commandBus->getSubscriptions() as $message => $handler) {
            $this->clusterRegistry->unregisterMessage($this, $message);
        }
    }

    /**
     * @inheritDoc
     */
    protected function doProcessMessage(AMQPMessage $message)
    {
        try {
            $dispatchMessage = DispatchMessage::fromBytes($this->serializer, $message->body);
        } catch (\Exception $e) {
            $this->sendReply(new ReplyMessage($message->get('correlation_id'), $this->serializer, $e, false), $message);
            return;
        }

        $resultCallback = new ResultCallback();
        $reply = null;

        try {
            $this->commandBus->dispatch($dispatchMessage->getCommandMessage(), $resultCallback);
            $reply = new ReplyMessage(
                $dispatchMessage->getCommandIdentifier(),
                $this->serializer,
                $resultCallback->getResult()
            );
        } catch (\Exception $e) {
            $reply = new ReplyMessage($dispatchMessage->getCommandIdentifier(), $this->serializer, $e, false);
        } finally {
            if ($dispatchMessage->isExpectReply()) {
                $this->sendReply($reply, $message);
            }
        }
    }

    private function sendReply(ReplyMessage $reply, AMQPMessage $message)
    {
        $amqpMessage = new AMQPMessage(
            $reply->toBytes(),
            ['correlation_id' => $message->get('correlation_id')]
        );

        $message->delivery_info['channel']->basic_publish($amqpMessage, '', $message->get('reply_to'));
        $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
    }

    /**
     * @inheritdoc
     */
    protected function doStop()
    {
        $this->getChannel()->queue_delete($this->getEndpoint(), false, true);
    }

    /**
     * @inheritdoc
     */
    protected function doStart()
    {
        $this->logger->info('Declaring queue [{queue}]', ['queue' => $this->getEndpoint()]);

        $this->getChannel()->queue_declare($this->getEndpoint(), false, false, false, false);
        $this->getChannel()->basic_qos(null, 1, null);
        $this->getChannel()->basic_consume(
            $this->getEndpoint(),
            '',
            false,
            false,
            false,
            false,
            [$this, 'processMessage']
        );

        while (count($this->getChannel()->callbacks)) {
            $this->getChannel()->wait();
        }

        $this->getChannel()->close();
        $this->getChannel()->close();
    }

}