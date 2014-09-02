<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\CommandHandling\Handlers;

use Governor\Framework\CommandHandling\CommandMessageInterface;
use Governor\Framework\UnitOfWork\UnitOfWorkInterface;

/**
 * Description of GenericCommandHandler
 *
 * @author david
 */
class AnnotatedCommandHandler extends AbstractAnnotatedCommandHandler
{

    protected $commandTarget;

    public function __construct($commandName, $methodName, $commandTarget)
    {
        parent::__construct($commandName, $methodName);
        $this->commandTarget = $commandTarget;
    }

    public function handle(CommandMessageInterface $commandMessage,
        UnitOfWorkInterface $unitOfWork)
    {
        $this->verifyCommandMessage($commandMessage);
        return $this->invoke($commandMessage);
    }

    protected function invoke(CommandMessageInterface $message)
    {
        $reflectionMethod = new \ReflectionMethod($this->commandTarget,
            $this->methodName);

        return  $reflectionMethod->invokeArgs($this->commandTarget,
                array($message->getPayload()));                       
    }

}
