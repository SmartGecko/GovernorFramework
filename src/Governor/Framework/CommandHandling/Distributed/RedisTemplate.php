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

use Predis\Client;

class RedisTemplate
{

    /**
     * Queue (LIST) name with node name as parameter.
     */
    const COMMAND_QUEUE_KEY = 'command:queue:%s';

    /**
     *  Command (KEY) response with the command id as parameter.
     */
    const COMMAND_RESPONSE_KEY = 'command:response:%s';
    const COMMAND_ROUTING_KEY = 'command:routing';

    /**
     * Subscription (SET) holding the list of subscribed handlers/nodes for each hash.
     */
    const COMMAND_SUBSCRIPTION_KEY = 'command:subscription:%s';

    const DEFAULT_BLOCK_TIMEOUT = 10;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var string
     */
    private $nodeName;

    /**
     * @var int
     */
    private $timeout;

    /**
     * @param string $connection
     * @param string $nodeName
     * @param array $options
     */
    function __construct($connection, $nodeName, $options = [])
    {
        $this->client = new Client($connection,
            array_merge($options,
                [
                    'prefix' => 'governor:',
                    'exceptions' => true
                ]
            )
        );

        $this->nodeName = $nodeName;
        $this->timeout = self::DEFAULT_BLOCK_TIMEOUT;
    }

    /**
     * @param string $commandName
     * @param string $routingKey
     * @return string|null
     */
    public function getRoutingDestination($commandName, $routingKey) // TODO routing has to be remade this is just a POC
    {
        return $this->client->hget(self::COMMAND_ROUTING_KEY, $this->hashCommandRouting($commandName, $routingKey));
    }

    /**
     * @param string $destination
     * @param string $commandName
     * @param string $routingKey
     */
    public function setRoutingDestination($destination, $commandName, $routingKey)
    {
        $this->client->hset(
            self::COMMAND_ROUTING_KEY,
            $this->hashCommandRouting($commandName, $routingKey),
            $destination
        );
    }

    /**
     * @param string $destination
     * @param string $data
     */
    public function enqueueCommand($destination, $data)
    {
        $this->client->rpush(sprintf(self::COMMAND_QUEUE_KEY, $destination), [$data]);
    }

    /**
     * Returns an array of pending commands on the destination queue.
     * If the destination is <code>null</code> commands from the local node queue are returned.
     *
     * @param string|null $destination
     * @return array
     */
    public function getPendingCommands($destination = null)
    {
        $destination = $destination ? $destination : $this->nodeName;

        return $this->client->lrange(sprintf(self::COMMAND_QUEUE_KEY, $destination), 0, -1);
    }

    /**
     * @param int $timeout
     * @return array
     */
    public function dequeueCommand($timeout = null)
    {
        $timeout = $timeout ? $timeout : $this->timeout;

        return $this->client->blpop([sprintf(self::COMMAND_QUEUE_KEY, $this->nodeName)], $timeout);
    }

    /**
     * @param string $commandIdentifier
     * @param mixed $reply
     */
    public function writeCommandReply($commandIdentifier, $reply)
    {
        $this->client->rpush(sprintf(self::COMMAND_RESPONSE_KEY, $commandIdentifier), [$reply]);
        $this->client->expire(sprintf(self::COMMAND_RESPONSE_KEY, $commandIdentifier), $this->timeout);
    }

    /**
     * @param string $commandIdentifier
     * @param int $timeout
     * @return array
     */
    public function readCommandReply($commandIdentifier, $timeout = null)
    {
        $timeout = $timeout ? $timeout : $this->timeout;

        return $this->client->blpop([sprintf(self::COMMAND_RESPONSE_KEY, $commandIdentifier)], $timeout);
    }

    /**
     * @param string $commandName
     * @return array
     */
    public function getSubscriptions($commandName)
    {
        return $this->client->smembers(
            sprintf(self::COMMAND_SUBSCRIPTION_KEY, $this->hashCommandName($commandName))
        );
    }

    /**
     * Adds the command to the subscription set.
     *
     * @param string $commandName
     */
    public function subscribe($commandName)
    {
        $this->client->sadd(
            sprintf(self::COMMAND_SUBSCRIPTION_KEY, $this->hashCommandName($commandName)),
            [$this->nodeName]
        );
    }

    /**
     * Removes the command from the subscription set.
     *
     * @param string $commandName
     */
    public function unsubscribe($commandName)
    {
        $this->client->srem(
            sprintf(self::COMMAND_SUBSCRIPTION_KEY, $this->hashCommandName($commandName)),
            [$this->nodeName]
        );
    }

    public static function hashCommandName($commandName)
    {
        return hash('md5', $commandName);
    }

    public static function hashCommandRouting($commandName, $routingKey)
    {
        return hash('md5', sprintf('%s-%s', $commandName, $routingKey));
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @return string
     */
    public function getNodeName()
    {
        return $this->nodeName;
    }

    /**
     * @return int
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * @param int $timeout
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
    }

}