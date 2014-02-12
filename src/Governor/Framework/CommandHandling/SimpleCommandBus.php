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

    protected $locator;

    public function __construct(CommandHandlerLocatorInterface $locator)
    {
        $this->locator = $locator;
    }

    public function dispatch(CommandMessageInterface $command,
        CommandCallback $callback = null)
    {
        $handler = $this->locator->findCommandHandlerFor($command);

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
}
