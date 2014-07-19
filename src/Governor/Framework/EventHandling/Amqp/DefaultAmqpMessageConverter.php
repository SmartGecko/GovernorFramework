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

use Governor\Framework\EventHandling\Io\EventMessageWriter;
use Governor\Framework\Domain\EventMessageInterface;
use Governor\Framework\Serializer\SerializerInterface;

/**
 * Description of DefaultAMQPMessageConverter
 *
 * @author david
 */
class DefaultAmqpMessageConverter implements AmqpMessageConverterInterface
{

    private $serializer;
    private $routingKeyResolver;
    private $durable;

    /**
     * Initializes the AMQPMessageConverter with the given <code>serializer</code>, <code>routingKeyResolver</code> and
     * requesting durable dispatching when <code>durable</code> is <code>true</code>.
     *
     * @param serializer         The serializer to serialize the Event Message's payload and Meta Data with
     * @param routingKeyResolver The strategy to use to resolve routing keys for Event Messages
     * @param durable            Whether to request durable message dispatching
     */
    public function __construct(SerializerInterface $serializer,
            RoutingKeyResolverInterface $routingKeyResolver = null,
            $durable = true)
    {

        $this->serializer = $serializer;
        $this->routingKeyResolver = null === $routingKeyResolver ? new NamespaceRoutingKeyResolver()
                    : $routingKeyResolver;
        $this->durable = $durable;
    }

    public function createAmqpMessage(EventMessageInterface $eventMessage)
    {
        $writer = new EventMessageWriter($this->serializer);
        
        $body = $writer->writeEventMessage($eventMessage);
        $routingKey = $this->routingKeyResolver->resolveRoutingKey($eventMessage);

        if ($this->durable) {
            return new AmqpMessage($body, $routingKey,
                    array('delivery_mode' => 2), false, false);
        }

        return new AmqpMessage($body, $routingKey);
    }

    public function readAmqpMessage($messageBody, array $headers)
    {
        return null;      
    }
  
}
