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

use Governor\Framework\CommandHandling\CommandMessageInterface;

/**
 * Abstract implementation of the RoutingStrategy interface that uses a policy to prescribe what happens when a routing
 * cannot be resolved.
 *
 * @author Allard Buijze
 * @since 2.0
 */
abstract class AbstractRoutingStrategy implements RoutingStrategyInterface
{
    const STATIC_ROUTING_KEY = 'unresolved';

    /**
     * @var int
     */
    private $unresolvedRoutingKeyPolicy;
//private final AtomicLong counter = new AtomicLong(0);

    /**
     * Initializes the strategy using given <code>unresolvedRoutingKeyPolicy</code> prescribing what happens when a
     * routing key cannot be resolved.
     *
     * @param int $unresolvedRoutingKeyPolicy The policy for unresolved routing keys.
     */
    public function  __construct($unresolvedRoutingKeyPolicy)
    {
        $this->unresolvedRoutingKeyPolicy = $unresolvedRoutingKeyPolicy;
    }


    public function getRoutingKey(CommandMessageInterface $command)
    {
        $routingKey = $this->doResolveRoutingKey($command);

        if (null === $routingKey) {
            switch ($this->unresolvedRoutingKeyPolicy) {
                case UnresolvedRoutingKeyPolicy::ERROR:
                    throw new CommandDispatchException(
                        sprintf(
                            "The command [%s] does not contain a routing key.",
                            $command->getCommandName()
                        )
                    );
                case UnresolvedRoutingKeyPolicy::RANDOM_KEY:
                    return null; // TODO return Long.toHexString(counter.getAndIncrement());
                case UnresolvedRoutingKeyPolicy::STATIC_KEY:
                    return self::STATIC_ROUTING_KEY;
            }
        }

        return $routingKey;
    }

    /**
     * Resolve the Routing Key for the given <code>command</code>.
     *
     * @param CommandMessageInterface $command The command to resolve the routing key for
     * @return string the String representing the Routing Key, or <code>null</code> if unresolved.
     */
    abstract protected function doResolveRoutingKey(CommandMessageInterface $command);
}