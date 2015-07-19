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

namespace Governor\Framework\EventHandling\Amqp;

use Governor\Framework\Common\Logging\NullLogger;
use Governor\Framework\EventHandling\TerminalInterface;
use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage as RawMessage;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Governor\Framework\Serializer\SerializerInterface;
use Governor\Framework\UnitOfWork\CurrentUnitOfWork;

/**
 * Implementation of the {@see TerminalInterface} supporting the AMQP protocol.
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
class AmqpTerminal implements TerminalInterface,  LoggerAwareInterface
{

    const DEFAULT_EXCHANGE_NAME = "Governor.EventBus";

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var AMQPConnection
     */
    private $connection;

    /**
     * @var string
     */
    private $exchangeName = self::DEFAULT_EXCHANGE_NAME;

    /**
     * @var boolean
     */
    private $isTransactional = false;

    /**
     * @var boolean
     */
    private $isDurable = true;
    //  private ListenerContainerLifecycleManager listenerContainerLifecycleManager;

    /**
     * @var AMQPMessageConverterInterface
     */
    private $messageConverter;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var RoutingKeyResolverInterface
     */
    private $routingKeyResolver;

    /**
     * @var boolean
     */
    private $waitForAck;

    /**
     * @var integer
     */
    private $publisherAckTimeout = 0;

    public function __construct(
        SerializerInterface $serializer,
        AmqpMessageConverterInterface $messageConverter = null
    ) {
        $this->serializer = $serializer;
        $this->logger = new NullLogger();
        $this->routingKeyResolver = new NamespaceRoutingKeyResolver();
        $this->messageConverter = null === $messageConverter ? new DefaultAmqpMessageConverter(
            $this->serializer,
            $this->routingKeyResolver, $this->isDurable
        ) : $messageConverter;
    }

    private function tryClose(AMQPChannel $channel)
    {
        try {
            $channel->close();
        } catch (\Exception $ex) {
            $this->logger->info("Unable to close channel. It might already be closed.");
        }
    }

    /**
     * Does the actual publishing of the given <code>body</code> on the given <code>channel</code>. This method can be
     * overridden to change the properties used to send a message.
     *
     * @param AMQPChannel $channel The channel to dispatch the message on
     * @param AmqpMessage $amqpMessage The AMQPMessage describing the characteristics of the message to publish
     */
    protected function doSendMessage(
        AMQPChannel $channel,
        AmqpMessage $amqpMessage
    ) {
        $rawMessage = new RawMessage(
            $amqpMessage->getBody(),
            $amqpMessage->getProperties()
        );

        $this->logger->debug(
            "Publishing message to {exchange} with routing key {key}",
            array(
                'exchange' => $this->exchangeName,
                'key' => $amqpMessage->getRoutingKey()
            )
        );

        $channel->basic_publish(
            $rawMessage,
            $this->exchangeName,
            $amqpMessage->getRoutingKey(),
            $amqpMessage->isMandatory(),
            $amqpMessage->isImmediate()
        );
    }

    private function tryRollback(AMQPChannel $channel)
    {
        try {
            $channel->tx_rollback();
        } catch (\Exception $ex) {
            $this->logger->debug("Unable to rollback. The underlying channel might already be closed.");
        }
    }

    /**
     * Sets the Connection providing the Channels to send messages on.
     * <p/>
     *
     * @param AMQPConnection $connection The connection to set
     */
    public function setConnection(AMQPConnection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Whether this Terminal should dispatch its Events in a transaction or not. Defaults to <code>false</code>.
     * <p/>
     * If a delegate Terminal  is configured, the transaction will be committed <em>after</em> the delegate has
     * dispatched the events.
     * <p/>
     * Transactional behavior cannot be enabled if {@link #setWaitForPublisherAck(boolean)} has been set to
     * <code>true</code>.
     *
     * @param boolean $transactional whether dispatching should be transactional or not
     */
    public function setTransactional($transactional)
    {
        if (!$this->waitForAck || !$transactional) {
            $this->isTransactional = $transactional;
        } else {
            throw new \LogicException("Cannot set transactional behavior when 'waitForServerAck' is enabled.");
        }
    }

    /**
     * Enables or diables the RabbitMQ specific publisher acknowledgements (confirms). When confirms are enabled, the
     * terminal will wait until the server has acknowledged the reception (or fsync to disk on persistent messages) of
     * all published messages.
     * <p/>
     * Server ACKS cannot be enabled when transactions are enabled.
     * <p/>
     * See <a href="http://www.rabbitmq.com/confirms.html">RabbitMQ Documentation</a> for more information about
     * publisher acknowledgements.
     *
     * @param boolean $waitForPublisherAck whether or not to enab;e server acknowledgements (confirms)
     */
    public function setWaitForPublisherAck($waitForPublisherAck)
    {
        if (!$waitForPublisherAck || !$this->isTransactional) {
            $this->waitForAck = $waitForPublisherAck;
        } else {
            throw new \LogicException("Cannot set 'waitForPublisherAck' when using transactions.");
        }
    }

    /**
     * Sets the maximum amount of time (in milliseconds) the publisher may wait for the acknowledgement of published
     * messages. If not all messages have been acknowledged withing this time, the publication will throw an
     * EventPublicationFailedException.
     * <p/>
     * This setting is only used when {@link #setWaitForPublisherAck(boolean)} is set to <code>true</code>.
     *
     * @param integer $publisherAckTimeout The number of milliseconds to wait for confirms, or 0 to wait indefinitely.
     */
    public function setPublisherAckTimeout($publisherAckTimeout)
    {
        $this->publisherAckTimeout = $publisherAckTimeout;
    }

    /*
     * Sets the Message Converter that creates AMQP Messages from Event Messages and vice versa. Setting this property
     * will ignore the "durable", "serializer" and "routingKeyResolver" properties, which just act as short hands to
     * create a DefaultAMQPMessageConverter instance.
     * <p/>
     * Defaults to a DefaultAMQPMessageConverter.
     *
     * @param messageConverter The message converter to convert AMQP Messages to Event Messages and vice versa.
     */
    //  public void setMessageConverter(AMQPMessageConverter messageConverter) {
    //      this.messageConverter = messageConverter;
    //  }

    /**
     * Whether or not messages should be marked as "durable" when sending them out. Durable messages suffer from a
     * performance penalty, but will survive a reboot of the Message broker that stores them.
     * <p/>
     * By default, messages are durable.
     * <p/>
     * Note that this setting is ignored if a {@link
     * #setMessageConverter(org.axonframework.eventhandling.amqp.AMQPMessageConverter) MessageConverter} is provided.
     * In that case, the message converter must add the properties to reflect the required durability setting.
     *
     * @param boolean $durable whether or not messages should be durable
     */
    public function setDurable($durable)
    {
        $this->isDurable = $durable;
    }

    /**
     * Sets the name of the exchange to dispatch published messages to. Defaults to "{@value #DEFAULT_EXCHANGE_NAME}".
     *
     * @param string $exchangeName the name of the exchange to dispatch messages to
     */
    public function setExchangeName($exchangeName)
    {
        $this->exchangeName = $exchangeName;
    }

    public function publish(array $events)
    {
        if (null === $this->connection) {
            throw new \RuntimeException("The AMQPTerminal has no connection configured.");
        }

        $channel = $this->connection->channel();

        if ($this->isTransactional) {
            $channel->tx_select();
        }

        try {
            if ($this->waitForAck) {
                $channel->confirm_select();
            }

            foreach ($events as $event) {
                $amqpMessage = $this->messageConverter->createAmqpMessage($event);
                $this->doSendMessage($channel, $amqpMessage);
            }

            if (CurrentUnitOfWork::isStarted()) {
                CurrentUnitOfWork::get()->registerListener(
                    new ChannelTransactionUnitOfWorkListener(
                        $this->logger,
                        $channel, $this
                    )
                );
            } elseif ($this->isTransactional) {
                $channel->tx_commit();
            } elseif ($this->waitForAck) {
                $channel->wait_for_pending_acks($this->publisherAckTimeout);
            }
        } catch (\Exception $ex) {
            if ($this->isTransactional) {
                $this->tryRollback($channel);
            }

            throw new EventPublicationFailedException(
                "Failed to dispatch Events to the Message Broker.",
                0, $ex
            );
        } finally {
            if (!CurrentUnitOfWork::isStarted()) {
                $this->tryClose($channel);
            }
        }
    }

    /**
     * @param LoggerInterface $logger
     * @return null
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Sets the RoutingKeyResolver that provides the Routing Key for each message to dispatch.
     *
     * @param RoutingKeyResolverInterface $routingKeyResolver the RoutingKeyResolver to use
     */
    public function setRoutingKeyResolver(RoutingKeyResolverInterface $routingKeyResolver)
    {
        $this->routingKeyResolver = $routingKeyResolver;
    }

}
