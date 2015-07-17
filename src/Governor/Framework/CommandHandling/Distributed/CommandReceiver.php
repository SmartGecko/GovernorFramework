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

use Governor\Framework\CommandHandling\Callbacks\ResultCallback;
use Governor\Framework\Common\ReceiverInterface;
use Governor\Framework\CommandHandling\CommandBusInterface;
use Governor\Framework\Serializer\SerializerInterface;
use Psr\Log\LoggerAwareInterface;
use Governor\Framework\Common\Logging\NullLogger;
use Psr\Log\LoggerInterface;

/**
 * Receiver that forwards incoming distributed commands to the local command bus.
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
class CommandReceiver implements ReceiverInterface, LoggerAwareInterface
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
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param RedisTemplate $template
     * @param CommandBusInterface $localSegment
     * @param SerializerInterface $serializer
     */
    function __construct(RedisTemplate $template, CommandBusInterface $localSegment, SerializerInterface $serializer)
    {
        $this->template = $template;
        $this->localSegment = $localSegment;
        $this->serializer = $serializer;
        $this->logger = new NullLogger();
    }

    public function run()
    {
        while (true) {
            try {
                $data = $this->template->dequeueCommand();
                $dispatchMessage = DispatchMessage::fromBytes($this->serializer, $data[1]);

                $callback = new ResultCallback();
                $this->localSegment->dispatch($dispatchMessage->getCommandMessage(), $callback);

                try {
                    $result = $callback->getResult();

                    if ($dispatchMessage->isExpectReply()) {
                        $this->template->writeCommandReply($dispatchMessage->getCommandIdentifier(), $result);
                    }
                } catch (\Exception $ex) {
                    $this->template->writeCommandReply($dispatchMessage->getCommandIdentifier(), $ex->getMessage());
                }

            } catch (\Exception $ex) {
                $this->logger->error(
                    'Exception on node {node} while processing command: {message}',
                    [
                        'node' => $this->template->getNodeName(),
                        'message' => $ex->getMessage()
                    ]
                );
            }
        }
    }

    /**
     * Sets a logger instance on the object
     *
     * @param LoggerInterface $logger
     * @return null
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }


}