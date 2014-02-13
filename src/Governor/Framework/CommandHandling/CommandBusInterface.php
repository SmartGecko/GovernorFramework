<?php

namespace Governor\Framework\CommandHandling;

use Governor\Framework\CommandHandling\CommandMessageInterface;

/**
 * Accept and process commands by passing them along to a matching command handler.
 */
interface CommandBusInterface
{

    public function dispatch(CommandMessageInterface $command,
        CommandCallback $callback = null);    

    /**
     * Subscribe the given <code>handler</code> to commands of type <code>commandType</code>.
     * <p/>
     * If a subscription already exists for the given type, the behavior is undefined. Implementations may throw an
     * Exception to refuse duplicate subscription or alternatively decide whether the existing or new
     * <code>handler</code> gets the subscription.
     *
     * @param commandName The name of the command to subscribe the handler to
     * @param handler     The handler service that handles the given type of command
     * @param <C>         The Type of command
     */
    public function subscribe($commandName, CommandHandlerInterface $handler);

    /**
     * Unsubscribe the given <code>handler</code> to commands of type <code>commandType</code>. If the handler is not
     * currently assigned to that type of command, no action is taken.
     *
     * @param commandName The name of the command the handler is subscribed to
     * @param handler     The handler service to unsubscribe from the CommandBus
     * @param <C>         The Type of command
     * @return <code>true</code> of this handler is successfully unsubscribed, <code>false</code> of the given
     *         <code>handler</code> was not the current handler for given <code>commandType</code>.
     */
    public function unsubscribe($commandName, CommandHandlerInterface $handler);
}
