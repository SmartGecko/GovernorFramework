<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\CommandHandling;

/**
 * Description of InMemoryCommandHandlerLocator
 *
 * @author david
 */
class InMemoryCommandHandlerLocator implements CommandHandlerLocatorInterface
{

    private $subscriptions = array();

    public function findCommandHandlerFor(CommandMessageInterface $command)
    {
        if (!array_key_exists($command->getCommandName(), $this->subscriptions)) {
            throw new NoHandlerForCommandException(sprintf("No handler was subscribed for command [%s]",
                $command->getCommandName()));
        }

        return $this->subscriptions[$command->getCommandName()];
    }

    public function subscribe($commandName, $handler)
    {
        $this->subscriptions[$commandName] = $handler;
    }

    public function unsubscribe($commandName, $handler)
    {
        if (isset($this->subscriptions[$commandName])) {
            unset($this->subscriptions[$commandName]);
        }
    }

}
