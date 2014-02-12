<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\CommandHandling;

use Governor\Framework\UnitOfWork\DefaultUnitOfWork;

/**
 * Description of SimpleCommandBus
 *
 * @author david
 */
class SimpleCommandBus implements CommandBusInterface
{

    protected $subscriptions = array();

    public function __construct()
    {
        
    }

    public function dispatch(CommandMessageInterface $command,
        CommandCallback $callback = null)
    {
        $handler = $this->findCommandHandlerFor($command);

        if (null === $callback) {
            $callback = new CommandCallback(function($result) {
                
            }, function ($err) {
                
            });
        }

        try {
            $result = $this->doDispatch($command, $handler);
            $callback->onSuccess($result);
        } catch (Exception $ex) {
            $callback->onFailure($ex);
        }
    }

    protected function doDispatch(CommandMessageInterface $command,
        CommandHandlerInterface $handler)
    {
        $unitOfWork = DefaultUnitOfWork::startAndGet();
        
        try {
            $return = $handler->handle($command, $unitOfWork);
        } catch (\Exception $ex) {
            $unitOfWork->rollback();

            throw $ex;
        }

        $unitOfWork->commit();
        
        return $return;
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

    public function findCommandHandlerFor(CommandMessageInterface $command)
    {
        if (!array_key_exists($command->getCommandName(), $this->subscriptions)) {
            throw new NoHandlerForCommandException(sprintf("No handler was subscribed for command [%s]",
                $command->getCommandName()));
        }

        return $this->subscriptions[$command->getCommandName()];
    }

}
