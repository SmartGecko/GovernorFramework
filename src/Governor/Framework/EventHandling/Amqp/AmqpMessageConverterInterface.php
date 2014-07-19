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

use Governor\Framework\Domain\EventMessageInterface;

/**
 * Interface describing a mechanism that converts AMQP Messages from an Governor Messages and vice versa.
 * 
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a> 
 */
interface AmqpMessageConverterInterface
{

    /**
     * Creates an AmqpMessage from given <code>eventMessage</code>.
     *
     * @param EventMessageInterface $eventMessage The EventMessage to create the AMQP Message from
     * @return AmqpMessage An AMQP Message containing the data and characteristics of the Message to send to the AMQP Message
     *         Broker.
     */
    public function createAmqpMessage(EventMessageInterface $eventMessage);

    /**
     * Reconstruct an EventMessage from the given <code>messageBody</code> and <code>headers</code>.
     *
     * @param mixed $messageBody The body of the AMQP Message
     * @param array $headers     The headers attached to the AMQP Message
     * @return EventMessageInterface The Event Message to publish on the local clusters
     */
    public function readAmqpMessage($messageBody, array $headers);
}
