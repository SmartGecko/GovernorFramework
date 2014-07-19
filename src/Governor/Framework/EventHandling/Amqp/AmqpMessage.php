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

/**
 * Representation of an AMQP Message. Used by AMQP Based Terminals to define the settings to use when dispatching an
 * Event to an AMQP Message Broker.
 * 
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a> 
 */
class AmqpMessage
{

    /**
     * @var mixed
     */
    private $body;

    /**
     * @var string
     */
    private $routingKey;

    /**
     * @var array
     */
    private $properties;

    /**
     * @var boolean
     */
    private $mandatory;

    /**
     * @var boolean
     */
    private $immediate;

    /**
     * Creates an AMQPMessage. The given parameters define the properties returned by this instance.
     *
     * @param mixed $body       The body of the message
     * @param string $routingKey The routing key of the message
     * @param array $properties The properties defining AMQP specific characteristics of the message
     * @param boolean $mandatory  Whether the message is mandatory (i.e. at least one destination queue MUST be available)
     * @param boolean $immediate  Whether the message must be delivered immediately (i.e. a Consumer must be connected and
     *                   capable of reading the message right away).
     */
    public function __construct($body, $routingKey, $properties = array(),
            $mandatory = false, $immediate = false)
    {
        $this->body = $body;
        $this->routingKey = $routingKey;
        $this->properties = $properties;
        $this->mandatory = $mandatory;
        $this->immediate = $immediate;
    }

    /**
     * Returns the body of this message
     *
     * @return mixed the body of this message
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Returns the Routing Key this message should be dispatched with
     *
     * @return string the Routing Key this message should be dispatched with
     */
    public function getRoutingKey()
    {
        return $this->routingKey;
    }

    /**
     * Returns the additional properties to dispatch this Message with
     *
     * @return array the additional properties to dispatch this Message with
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * Whether to dispatch this message using the "mandatory" flag
     *
     * @return boolean whether to dispatch this message using the "mandatory" flag
     */
    public function isMandatory()
    {
        return $this->mandatory;
    }

    /**
     * Whether to dispatch this message using the "immediate" flag
     *
     * @return boolean whether to dispatch this message using the "immediate" flag
     */
    public function isImmediate()
    {
        return $this->immediate;
    }

}
