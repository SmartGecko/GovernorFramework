<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\CommandHandling\Handlers;

use Governor\Framework\CommandHandling\CommandHandlerInterface;
use Governor\Framework\CommandHandling\CommandMessageInterface;

/**
 * Description of AbstractAnnotatedCommandHandler
 *
 * @author david
 */
abstract class AbstractAnnotatedCommandHandler implements CommandHandlerInterface
{

    protected $commandName;
    protected $methodName;

    function __construct($commandName, $methodName)
    {
        $this->commandName = $commandName;
        $this->methodName = $methodName;
    }

    protected function verifyCommandMessage(CommandMessageInterface $message)
    {
        if ($message->getCommandName() !== $this->commandName) {
            throw new \BadMethodCallException(sprintf("Invalid command in handler %s, expected %s but got %s",
                get_class($this), $this->commandName, $message->getCommandName()));
        }
    }

}
