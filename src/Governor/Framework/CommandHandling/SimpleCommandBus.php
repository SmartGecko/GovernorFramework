<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\CommandHandling;

use Psr\Log\LoggerInterface;
use Governor\Framework\UnitOfWork\DefaultUnitOfWork;

/**
 * Description of SimpleCommandBus
 *
 * @author david
 */
class SimpleCommandBus implements CommandBusInterface
{

    private $subscriptions = array();

    /**
     * @var LoggerInterface 
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        ;
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
        } catch (\Exception $ex) {
            $callback->onFailure($ex);
            throw $ex;
        }
    }

    protected function doDispatch(CommandMessageInterface $command,
            CommandHandlerInterface $handler)
    {
        $this->logger->debug("Dispatching command [{name}]",
                array('name' => $command->getCommandName()));
        $unitOfWork = DefaultUnitOfWork::startAndGet($this->logger);

        try {
            $return = $handler->handle($command, $unitOfWork);
        } catch (\Exception $ex) {
            $unitOfWork->rollback();
            throw $ex;
        }

        $unitOfWork->commit();

        return $return;
    }

    public function findCommandHandlerFor(CommandMessageInterface $command)
    {
        if (!array_key_exists($command->getCommandName(), $this->subscriptions)) {
            throw new NoHandlerForCommandException(sprintf("No handler was subscribed for command [%s]",
                    $command->getCommandName()));
        }

        return $this->subscriptions[$command->getCommandName()];
    }

    public function subscribe($commandName, CommandHandlerInterface $handler)
    {
        $this->subscriptions[$commandName] = $handler;
    }

    public function unsubscribe($commandName, CommandHandlerInterface $handler)
    {
        if (isset($this->subscriptions[$commandName])) {
            unset($this->subscriptions[$commandName]);
        }
    }

}
