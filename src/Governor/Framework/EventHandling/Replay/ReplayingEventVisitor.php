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

namespace Governor\Framework\EventHandling\Replay;

use Psr\Log\LoggerInterface;
use Governor\Framework\EventHandling\ClusterInterface;
use Governor\Framework\Domain\DomainEventMessageInterface;
use Governor\Framework\EventStore\EventVisitorInterface;

/**
 * Description of ReplayingEventVisitor
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a> 
 */
class ReplayingEventVisitor implements EventVisitorInterface
{

    /**
     * @var ClusterInterface
     */
    private $delegate;

    /**
     * @var LoggerInterface
     */
    private $logger;

    function __construct(ClusterInterface $delegate, LoggerInterface $logger)
    {
        $this->delegate = $delegate;
        $this->logger = $logger;
    }

    public function doWithEvent(DomainEventMessageInterface $domainEvent)
    {
    /*    $this->logger->debug(sprintf("Visiting event %s with payload %s",
                        $domainEvent->getIdentifier(),
                        $domainEvent->getPayloadType()));*/
        $this->delegate->publish(array($domainEvent));
    }

}

/*
private int eventCounter = 0;
            private Object currentTransaction;

            public ReplayingEventVisitor(Object tx) {
                this.currentTransaction = tx;
            }

            @SuppressWarnings("unchecked")
            @Override
            public void doWithEvent(DomainEventMessage domainEvent) {
                if (commitThreshold > 0 && ++eventCounter > commitThreshold) {
                    eventCounter = 0;
                    logger.trace("Replay batch size reached; committing Replay Transaction");
                    transactionManager.commitTransaction(currentTransaction);
                    logger.trace("Starting new Replay Transaction for next batch");
                    currentTransaction = transactionManager.startTransaction();
                }
                delegate.publish(domainEvent);
                List<EventMessage> releasedMessages = incomingMessageHandler.releaseMessage(delegate, domainEvent);
                if (releasedMessages != null && !releasedMessages.isEmpty()) {
                    eventHandlingListeners.onEventProcessingCompleted(releasedMessages);
                }
            }

            public Object getTransaction() {
                return currentTransaction;
            }
        }
*/