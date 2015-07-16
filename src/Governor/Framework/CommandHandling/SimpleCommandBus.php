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

namespace Governor\Framework\CommandHandling;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareInterface;
use Governor\Framework\Common\Logging\NullLogger;
use Governor\Framework\CommandHandling\Callbacks\NoOpCallback;
use Governor\Framework\UnitOfWork\UnitOfWorkFactoryInterface;

/**
 * Simple command bus implementation.
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
class SimpleCommandBus implements CommandBusInterface, LoggerAwareInterface
{

    /**
     * @var CommandHandlerRegistryInterface
     */
    private $handlerRegistry;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var CommandHandlerInterceptorInterface[]
     */
    private $handlerInterceptors = [];

    /**
     * @var CommandDispatchInterceptorInterface[]
     */
    private $dispatchInterceptors = [];

    /**
     * @var UnitOfWorkFactoryInterface
     */
    private $unitOfWorkFactory;

    /**
     * @param CommandHandlerRegistryInterface $handlerRegistry
     * @param UnitOfWorkFactoryInterface $unitOfWorkFactory
     */
    public function __construct(
        CommandHandlerRegistryInterface $handlerRegistry,
        UnitOfWorkFactoryInterface $unitOfWorkFactory
    ) {
        $this->handlerRegistry = $handlerRegistry;
        $this->unitOfWorkFactory = $unitOfWorkFactory;
        $this->logger = new NullLogger();
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
     * @param CommandMessageInterface $command
     * @param CommandCallbackInterface $callback
     * @return mixed
     */
    public function dispatch(
        CommandMessageInterface $command,
        CommandCallbackInterface $callback = null
    ) {
        $handler = $this->handlerRegistry->findCommandHandlerFor($command);

        if (null === $callback) {
            $callback = new NoOpCallback();
        }

        $command = $this->intercept($command);

        try {
            $result = $this->doDispatch($command, $handler);
            $callback->onSuccess($result);
        } catch (\Exception $ex) {
            $callback->onFailure($ex);
        }
    }

    /**
     * @param CommandMessageInterface $command
     * @param CommandHandlerInterface $handler
     * @return mixed
     * @throws \Exception
     */
    protected function doDispatch(
        CommandMessageInterface $command,
        CommandHandlerInterface $handler
    ) {
        $this->logger->debug(
            "Dispatching command [{name}]",
            [
                'name' => $command->getCommandName()
            ]
        );

        $unitOfWork = $this->unitOfWorkFactory->createUnitOfWork();

        $chain = new DefaultInterceptorChain(
            $command, $unitOfWork, $handler,
            $this->handlerInterceptors
        );

        try {
            $return = $chain->proceed();
        } catch (\Exception $ex) {
            $unitOfWork->rollback($ex);
            throw $ex;
        }

        $unitOfWork->commit();

        return $return;
    }

    /**
     * Registers the given list of interceptors to the command bus. All incoming commands will pass through the
     * interceptors at the given order before the command is passed to the handler for processing.
     *
     * @param array $handlerInterceptors The interceptors to invoke when commands are handled
     */
    public function setHandlerInterceptors(array $handlerInterceptors)
    {
        $this->handlerInterceptors = $handlerInterceptors;
    }

    /**
     * Registers the given list of dispatch interceptors to the command bus. All incoming commands will pass through
     * the interceptors at the given order before the command is dispatched toward the command handler.
     *
     * @param array $dispatchInterceptors The interceptors to invoke when commands are dispatched
     */
    public function setDispatchInterceptors(array $dispatchInterceptors)
    {
        $this->dispatchInterceptors = $dispatchInterceptors;
    }

    /**
     * @param LoggerInterface $logger
     * @return null
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Returns the associated command handler registry.
     *
     * @return CommandHandlerRegistryInterface
     */
    public function getCommandHandlerRegistry()
    {
        return $this->handlerRegistry;
    }

    /**
     * Subscribe the given <code>handler</code> to commands of type <code>commandType</code>.
     * <p/>
     * If a subscription already exists for the given type, the behavior is undefined. Implementations may throw an
     * Exception to refuse duplicate subscription or alternatively decide whether the existing or new
     * <code>handler</code> gets the subscription.
     *
     * @param string $commandName The name of the command to subscribe the handler to
     * @param CommandHandlerInterface $handler The handler service that handles the given type of command
     */
    public function subscribe($commandName, CommandHandlerInterface $handler)
    {
        return $this->handlerRegistry->subscribe($commandName, $handler);
    }

    /**
     * Unsubscribe the given <code>handler</code> to commands of type <code>commandType</code>. If the handler is not
     * currently assigned to that type of command, no action is taken.
     *
     * @param string $commandName The name of the command the handler is subscribed to
     * @param CommandHandlerInterface $handler The handler service to unsubscribe from the CommandBus
     * @return boolean <code>true</code> of this handler is successfully unsubscribed, <code>false</code> of the given
     *         <code>handler</code> was not the current handler for given <code>commandType</code>.
     */
    public function unsubscribe($commandName, CommandHandlerInterface $handler)
    {
        return $this->handlerRegistry->unsubscribe($commandName, $handler);
    }


}
