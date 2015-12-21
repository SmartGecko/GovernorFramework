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


abstract class UnresolvedRoutingKeyPolicy
{
    /**
     * Policy that indicates that Routing Key resolution should fail with an Exception when no routing key can be found
     * for a Command Message.
     * <p/>
     * When the routing key is based on static content in the Command Message, the exception raised should extend from
     * {@link org.axonframework.common.AxonNonTransientException} to indicate that retries do not have a chance to
     * succeed.
     */
    const ERROR = 1;

    /**
     * Policy that indicates a random key is to be returned when no Routing Key can be found for a Command Message.
     * Although not required to be fully random, implementations are required to return a different key for each
     * incoming command. Multiple invocations for the same command message may return the same value, but are not
     * required to do so.
     * <p/>
     * This effectively means the Command Message is routed to a random segment.
     */
    const RANDOM_KEY = 2;

    /**
     * Policy that indicates a fixed key ("unresolved") should be returned when no Routing Key can be found for a
     * Command Message. This effectively means all Command Messages with unresolved routing keys are routed to a the
     * same segment. The load of that segment may therefore not match the load factor.
     */
    const STATIC_KEY = 3;
}