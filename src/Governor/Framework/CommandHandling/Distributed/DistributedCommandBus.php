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

use Governor\Framework\CommandHandling\Callbacks\NoOpCallback;
use Governor\Framework\CommandHandling\CommandBusInterface;
use Governor\Framework\CommandHandling\CommandCallbackInterface;
use Governor\Framework\CommandHandling\CommandHandlerInterface;
use Governor\Framework\CommandHandling\CommandMessageInterface;
use Governor\Framework\CommandHandling\CommandDispatchInterceptorInterface;
use Psr\Log\LoggerAwareInterface;
use Governor\Framework\Common\Logging\NullLogger;
use Psr\Log\LoggerInterface;

class DistributedCommandBus implements CommandBusInterface, LoggerAwareInterface
{

    const DISPATCH_ERROR_MESSAGE = 'An error occurred while trying to dispatch a command on the DistributedCommandBus';

    /**
     * @var CommandBusConnectorInterface
     */
    private $connector;

    /**
     * @var RoutingStrategyInterface
     */
    private $routingStrategy;

    /**
     * @var CommandDispatchInterceptorInterface[]
     */
    private $dispatchInterceptors;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param CommandBusConnectorInterface $connector
     * @param RoutingStrategyInterface $routingStrategy
     */
    function __construct(
        CommandBusConnectorInterface $connector,
        RoutingStrategyInterface $routingStrategy
    ) {
        $this->connector = $connector;
        $this->routingStrategy = $routingStrategy;
        $this->logger = new NullLogger();
    }


    /**
     * {@inheritdoc}
     */
    public function dispatch(
        CommandMessageInterface $command,
        CommandCallbackInterface $callback = null
    ) {
        $command = $this->intercept($command);
        $routingKey = $this->routingStrategy->getRoutingKey($command);

        $this->logger->debug(
            'Dispatching command [{name}] with routing key [{key}] in the DistributedCommandBus',
            [
                'name' => $command->getCommandName(),
                'key' => $routingKey
            ]
        );

        try {
            $this->connector->send($routingKey, $command, $callback);
        } catch (\Exception $ex) {
            $this->logger->error(
                self::DISPATCH_ERROR_MESSAGE.' {err}',
                [
                    'err' => $ex->getMessage()
                ]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function subscribe($commandName, CommandHandlerInterface $handler)
    {
        $this->connector->subscribe($commandName, $handler);
    }

    /**
     * {@inheritdoc}
     */
    public function unsubscribe($commandName, CommandHandlerInterface $handler)
    {
        $this->connector->unsubscribe($commandName, $handler);
    }

    /**
     * Invokes all the dispatch interceptors.
     *
     * @param CommandMessageInterface $command The original command being dispatched
     * @return CommandMessageInterface The command to actually dispatch
     */
    protected function intercept(CommandMessageInterface $command)
    {
        $commandToDispatch = $command;

        foreach ($this->dispatchInterceptors as $interceptor) {
            $commandToDispatch = $interceptor->dispatch($commandToDispatch);
        }

        return $commandToDispatch;
    }

    /**
     * @param CommandDispatchInterceptorInterface[] $dispatchInterceptors
     */
    public function setDispatchInterceptors(array $dispatchInterceptors)
    {
        $this->dispatchInterceptors = $dispatchInterceptors;
    }

    /**
     * {@inheritdoc}
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscriptions()
    {
        return [];
    }


}