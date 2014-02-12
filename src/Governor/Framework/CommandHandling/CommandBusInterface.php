<?php

namespace Governor\Framework\CommandHandling;

use Governor\Framework\CommandHandling\CommandMessageInterface;

/**
 * Accept and process commands by passing them along to a matching command handler.
 */
interface CommandBusInterface
{

    public function dispatch(CommandMessageInterface $command, CommandCallback $callback = null);
    
}
