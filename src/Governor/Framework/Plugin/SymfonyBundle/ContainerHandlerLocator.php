<?php

namespace Governor\Framework\Plugin\SymfonyBundle;

use Governor\Framework\EventHandling\EventHandlerLocator;
use Governor\Framework\CommandHandling\CommandHandlerLocator;
use Governor\Framework\EventHandling\EventName;

use Symfony\Component\DependencyInjection\ContainerInterface;

class ContainerHandlerLocator implements EventHandlerLocator, CommandHandlerLocator
{
    private $container;
    private $eventHandlers = array();
    private $commandHandlers = array();

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getHandlersFor(EventName $eventName)
    {
        $eventName = strtolower($eventName);

        if (!isset($this->eventHandlers[$eventName])) {
            return array();
        }

        $eventHandlers = array();
        foreach ($this->eventHandlers[$eventName] as $id) {
            $eventHandlers[] = $this->container->get($id);
        }

        return $eventHandlers;
    }

    public function getCommandHandler( $command)
    {
        return $this->container->get($this->commandHandlers[get_class($command)]);
    }

    public function registerEventHandlers($eventHandlers)
    {
        $this->eventHandlers = $eventHandlers;
    }

    public function registerCommandHandlers($commandHandlers)
    {
        $this->commandHandlers = $commandHandlers;
    }
}

