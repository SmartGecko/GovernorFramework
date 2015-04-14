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
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a> 
 */
interface AmqpConsumerConfigurationInterface
{

    /**
     * The key of the property in the Cluster Meta Data that reflects the AMQPConsumerConfiguration instance for that
     * cluster
     */
    const AMQP_CONFIG_PROPERTY = "AMQP.Config";

    /**
     * Returns the Queue Name the Cluster should be connected to, or <code>null</code> if no explicit cluster is
     * configured.
     *
     * @return string the Queue the cluster should be connected to, or <code>null</code> to revert to a default
     */
    public function getQueueName();

    /**
     * Indicates whether this Cluster wishes to be an exclusive consumer on a Queue. <code>null</code> indicated that
     * no explicit preference is provided, and a default should be used.
     *
     * @return boolean the exclusivity indicator for this cluster
     */
    public function getExclusive();

    /**
     * Indicates how many messages this Cluster's connector may read read from the Queue before expecting messages to
     * be acknowledged. <code>null</code> means no specific value is provided and a default should be used.
     *
     * @return integer the number of messages a Cluster's connector may read ahead before waiting for acknowledgements.
     */
    public function getPrefetchCount();
}
