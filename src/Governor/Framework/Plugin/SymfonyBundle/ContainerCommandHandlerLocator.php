<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Plugin\SymfonyBundle;

use Symfony\Component\DependencyInjection\ContainerAware;
use Governor\Framework\CommandHandling\CommandHandlerLocatorInterface;
use Governor\Framework\CommandHandling\NoHandlerForCommandException;
use Governor\Framework\CommandHandling\CommandMessageInterface;

/**
 * Description of ContainerCommandHandlerLocator
 *
 * @author david
 */
class ContainerCommandHandlerLocator extends ContainerAware implements CommandHandlerLocatorInterface
{

    private $subscriptions = array();

    public function findCommandHandlerFor(CommandMessageInterface $command)
    {
        if (!array_key_exists($command->getCommandName(), $this->subscriptions)) {
            throw new NoHandlerForCommandException(sprintf("No handler was subscribed for command [%s]",
                $command->getCommandName()));
        }

        return $this->container->get($this->subscriptions[$command->getCommandName()]);
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

    public function setSubscriptions(array $handlers)
    {
        foreach ($handlers as $commandName => $handler) {
            $this->subscribe($commandName, $handler);
        }
    }

}
