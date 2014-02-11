<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\CommandHandling;

use Doctrine\Common\Annotations\AnnotationReader;
use Governor\Framework\UnitOfWork\UnitOfWorkInterface;

/**
 * Description of GenericCommandHandler
 *
 * @author david
 */
class AnnotatedCommandHandlerAdapter implements CommandHandlerInterface
{

    private $handlerClass;
    private $reader;

    public function __construct($handlerClass)
    {
        $this->handlerClass = $handlerClass;
        $this->reader = new AnnotationReader();
    }

    public function handle(CommandMessageInterface $commandMessage,
        UnitOfWorkInterface $unitOfWork)
    {
        $reflectionClass = new \ReflectionClass($this->handlerClass);
        $payloadType = $commandMessage->getPayloadType();
        $handlerMethod = null;

        foreach ($reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            $annot = $this->reader->getMethodAnnotation($method,
                'Governor\Framework\Annotations\CommandHandler');

            // check commandName in annotation
            if (null !== $annot && $annot->commandName === $payloadType) {
                $handlerMethod = $method;
                break;
            }

            // check typehint of first parameter
            $commandArg = current($method->getParameters());
            if (null !== $commandArg->getClass() && $commandArg->getClass()->name === $payloadType) {
                $handlerMethod = $method;
                break;
            }
        }

        if (null === $handlerMethod) {
            throw new NoHandlerForCommandException(sprintf("No handler method found for %s in class %s",
                $payloadType, get_class($this)));
        }

        $handlerMethod->invokeArgs($this->handlerClass,
            array($commandMessage->getPayload()));
    }

}
