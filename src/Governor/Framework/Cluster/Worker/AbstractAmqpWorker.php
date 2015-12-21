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

namespace Governor\Framework\Cluster\Worker;

use Governor\Framework\Cluster\AbstractClusterNode;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Governor\Framework\Common\Logging\NullLogger;
use Governor\Framework\Serializer\SerializerInterface;

abstract class AbstractAmqpWorker extends AbstractClusterNode implements LoggerAwareInterface, WorkerInterface
{

    /**
     * @var AMQPStreamConnection
     */
    private $connection;

    /**
     * @var AMQPChannel
     */
    private $channel;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var int
     */
    private $consumed;

    /**
     * AbstractAmqpWorker constructor.
     *
     * @param string $nodeIdentifier
     * @param AMQPStreamConnection $connection
     * @param SerializerInterface $serializer
     */
    public function __construct($nodeIdentifier, AMQPStreamConnection $connection, SerializerInterface $serializer)
    {
        if (!extension_loaded('pcntl')) {
            throw new \RuntimeException('Required PCNTL extension not loaded.');
        }

        pcntl_signal(SIGTERM, [$this, 'signalHandler']);
        pcntl_signal(SIGHUP, [$this, 'signalHandler']);
        pcntl_signal(SIGINT, [$this, 'signalHandler']);
        pcntl_signal(SIGQUIT, [$this, 'signalHandler']);
        pcntl_signal(SIGUSR1, [$this, 'signalHandler']);
        pcntl_signal(SIGUSR2, [$this, 'signalHandler']);
        pcntl_signal(SIGALRM, [$this, 'alarmHandler']);

        parent::__construct($nodeIdentifier);

        $this->connection = $connection;
        $this->serializer = $serializer;

        $this->channel = $connection->channel();
        $this->logger = new NullLogger();
    }

    /**
     * @return string
     */
    public function getTag()
    {
        return $this->__toString();
    }

    /**
     * @return AMQPChannel
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @inheritDoc
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return SerializerInterface
     */
    public function getSerializer()
    {
        return $this->serializer;
    }

    /**
     * @param AMQPMessage $message
     * @return mixed
     */
    public function processMessage(AMQPMessage $message)
    {
        try {
            $this->doProcessMessage($message);
            $this->consumed++;
        } catch (\Exception $e) {
            $this->logger->error(
                'Exception with message [{msg}] caught while processing message',
                [
                    'msg' => $e->getMessage()
                ]
            );
        }
    }

    /**
     * Message processing login to be implemented in the subclass.
     *
     * @param AMQPMessage $message
     * @return mixed
     */
    protected abstract function doProcessMessage(AMQPMessage $message);

    /**
     * Start logic to be implemented in the subclass.
     */
    protected abstract function doStart();

    /**
     * Stop logic to be implemented in the subclass.
     */
    protected abstract function doStop();

    /**
     * Signal handler
     *
     * @param  int $signalNumber
     */
    public function signalHandler($signalNumber)
    {
        $this->logger->debug('Handling signal {signal}', ['signal' => $signalNumber]);

        switch ($signalNumber) {
            case SIGTERM:  // 15 : supervisor default stop
            case SIGQUIT:  // 3  : kill -s QUIT
                $this->stopHard();
                break;
            case SIGINT:   // 2  : ctrl+c
                $this->stop();
                break;
            case SIGHUP:   // 1  : kill -s HUP
                $this->restart();
                break;
            case SIGUSR1:  // 10 : kill -s USR1
                // send an alarm in 1 second
                pcntl_alarm(1);
                break;
            case SIGUSR2:  // 12 : kill -s USR2
                // send an alarm in 10 seconds
                pcntl_alarm(10);
                break;
            default:
                break;
        }
    }

    /**
     * Alarm handler
     *
     * @param  int $signalNumber
     * @return void
     */
    public function alarmHandler($signalNumber)
    {
        echo 'Handling alarm: #'.$signalNumber.PHP_EOL;

        echo memory_get_usage(true).PHP_EOL;

        return;
    }

    /**
     * Restart the consumer on an existing connection
     */
    public function restart()
    {
        echo 'Restarting consumer.'.PHP_EOL;
        $this->stopSoft();
        $this->start();
    }

    /**
     * Close the connection to the server
     */
    public function stopHard()
    {
        echo 'Stopping consumer by closing connection.'.PHP_EOL;
        $this->connection->close();
    }

    /**
     * Close the channel to the server
     */
    public function stopSoft()
    {
        echo 'Stopping consumer by closing channel.'.PHP_EOL;
        $this->channel->close();
    }

    /**
     * Tell the server you are going to stop consuming
     * It will finish up the last message and not send you any more
     */
    public function stop()
    {
        $this->logger->info('Stopping worker [{tag}]', ['tag' => $this->getTag()]);

        $this->doStop();
        // this gets stuck and will not exit without the last two parameters set
        $this->channel->basic_cancel($this->getTag(), false, true);

        $this->logger->info('Successfully stopped worker [{tag}]', ['tag' => $this->getTag()]);
    }

    public function start()
    {
        if (!$this->isJoined($this->getCluster())) {
            throw new WorkerException('Worker is not joined to a cluster');
        }

        $this->logger->info('Starting worker [{tag}]', ['tag' => $this->getTag()]);
        $this->doStart();
    }
}