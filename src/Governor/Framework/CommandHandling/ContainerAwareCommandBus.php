<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\CommandHandling;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Description of ContainerAwareCommandBus
 *
 * @author david
 */
class ContainerAwareCommandBus extends SimpleCommandBus implements ContainerAwareInterface
{

    private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function findCommandHandlerFor(CommandMessageInterface $command)
    {
        if (!array_key_exists($command->getCommandName(), $this->subscriptions)) {
            throw new NoHandlerForCommandException(sprintf("No handler was subscribed for command [%s]",
                $command->getCommandName()));
        }

        return $this->container->get($this->subscriptions[$command->getCommandName()]);
    }

}
