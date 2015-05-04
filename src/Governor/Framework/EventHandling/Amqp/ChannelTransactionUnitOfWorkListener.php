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

use Psr\Log\LoggerInterface;
use PhpAmqpLib\Channel\AMQPChannel;
use Governor\Framework\UnitOfWork\UnitOfWorkInterface;
use Governor\Framework\UnitOfWork\UnitOfWorkListenerAdapter;

/**
 * Description of ChannelTransactionUnitOfWorkListener
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a> 
 */
class ChannelTransactionUnitOfWorkListener extends UnitOfWorkListenerAdapter
{

    /**
     * @var AMQPChannel 
     */
    private $channel;

    /**
     * @var AmqpTerminal
     */
    private $terminal;

    /**
     * @var LoggerInterface 
     */
    private $logger;

    /**
     * 
     * @param LoggerInterface $logger
     * @param AMQPChannel $channel
     * @param AmqpTerminal $terminal
     */
    public function __construct(LoggerInterface $logger, AMQPChannel $channel,
            AmqpTerminal $terminal)
    {
        $this->logger = $logger;
        $this->channel = $channel;
        $this->terminal = $terminal;
        $this->isOpen = true;
    }

    private function getTerminalProperty($propertyName)
    {
        $reflClass = new \ReflectionClass($this->terminal);
        $property = $reflClass->getProperty($propertyName);
        $property->setAccessible(true);

        return $property->getValue($this->terminal);
    }

    private function closeTerminal()
    {
        $reflClass = new \ReflectionClass($this->terminal);
        $method = $reflClass->getMethod('tryClose');
        $method->setAccessible(true);

        $method->invoke($this->terminal, $this->channel);
    }

    public function onPrepareTransactionCommit(UnitOfWorkInterface $unitOfWork,
            $transaction)
    {
        if (($this->getTerminalProperty('isTransactional') || $this->getTerminalProperty('waitForAck'))
                && $this->isOpen && null === $this->channel->getChannelId()) {
            throw new EventPublicationFailedException(
            "Unable to Commit UnitOfWork changes to AMQP: Channel is closed.");
        }
    }

    public function afterCommit(UnitOfWorkInterface $unitOfWork)
    {
        if ($this->isOpen) {
            try {
                if ($this->getTerminalProperty('isTransactional')) {
                    $this->channel->tx_commit();
                } else if ($this->getTerminalProperty('waitForAck')) {
                    $this->waitForConfirmations();
                }
            } catch (\Exception $ex) {
                $this->logger->warn("Unable to commit transaction on channel.");
            }
            $this->closeTerminal();
        }
    }

    private function waitForConfirmations()
    {
        try {
            $this->channel->wait_for_pending_acks($this->getTerminalProperty('publisherAckTimeout'));
        } catch (\Exception $ex) {
            throw new EventPublicationFailedException("Failed to receive acknowledgements for all events");
        }
    }

    public function onRollback(UnitOfWorkInterface $unitOfWork,
            \Exception $failureCause = null)
    {
        try {
            if ($this->getTerminalProperty('isTransactional')) {
                $this->channel->tx_rollback();
            }
        } catch (\Exception $ex) {
            $this->logger->warn("Unable to rollback transaction on channel.",
                    $ex);
        }

        $this->closeTerminal();
        $this->isOpen = false;
    }

}
