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

use Governor\Framework\CommandHandling\GenericCommandMessage;
use Governor\Framework\Common\ReceiverInterface;
use Governor\Framework\Serializer\SimpleSerializedObject;
use Governor\Framework\Serializer\SimpleSerializedType;
use Predis\Client;
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
     * @var Client
     */
    private $client;

    /**
     * @var CommandBusInterface
     */
    private $localSegment;

    /**
     * @var string
     */
    private $nodeName;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var LoggerInterface
     */
    private $logger;

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
        $this->serializer = $serializer;
        $this->nodeName = $nodeName;
        $this->logger = new NullLogger();
    }

    public function run()
    {
        while (true) {
            try {
                $data = $this->client->blpop([sprintf('governor:command:%s:request', $this->nodeName)], 100);

                /** @var GenericCommandMessage $command */
                $command = $this->serializer->deserialize(
                    new SimpleSerializedObject($data, new SimpleSerializedType(GenericCommandMessage::class))
                );

                $this->localSegment->dispatch($command);
            } catch (\Exception $ex) {
                $this->logger->error('Exception on node {node} while processing command: {message}',
                    [
                        'node' => $this->nodeName,
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