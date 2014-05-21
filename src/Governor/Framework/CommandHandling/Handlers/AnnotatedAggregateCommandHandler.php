<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\CommandHandling\Handlers;

use Doctrine\Common\Annotations\AnnotationReader;
use Governor\Framework\CommandHandling\CommandBusInterface;
use Governor\Framework\CommandHandling\CommandMessageInterface;
use Governor\Framework\CommandHandling\CommandTargetResolverInterface;
use Governor\Framework\CommandHandling\AnnotationCommandTargetResolver;
use Governor\Framework\UnitOfWork\UnitOfWorkInterface;
use Governor\Framework\Repository\RepositoryInterface;

/**
 * Description of AggregateCommandHandler
 *
 * @author 255196
 */
class AnnotatedAggregateCommandHandler extends AbstractAnnotatedCommandHandler
{

    private $repository;
    private $aggregateType;
    private $targetResolver;

    public function __construct($commandName, $methodName, $aggregateType,
        RepositoryInterface $repository,
        CommandTargetResolverInterface $targetResolver = null)
    {
        parent::__construct($commandName, $methodName);
        $this->repository = $repository;
        $this->aggregateType = $aggregateType;
        $this->targetResolver = null === $targetResolver 
                ? new AnnotationCommandTargetResolver()
                : $targetResolver;                
    }

    public function handle(CommandMessageInterface $commandMessage,
        UnitOfWorkInterface $unitOfWork)
    {
        $this->verifyCommandMessage($commandMessage);

        switch ($this->methodName) {
            case '__construct':
                $this->handleConstructor($commandMessage, $unitOfWork);
                break;
            default:
                $this->handleMethod($commandMessage, $unitOfWork);
        }
    }

    private function handleConstructor(CommandMessageInterface $commandMessage,
        UnitOfWorkInterface $unitOfWork)
    {
        $reflectionClass = new \ReflectionClass($this->aggregateType);
        $object = $reflectionClass->newInstanceArgs(array($commandMessage->getPayload()));

        $this->repository->add($object);
    }

    private function handleMethod(CommandMessageInterface $commandMessage,
        UnitOfWorkInterface $unitOfWork)
    {
        $versionedId = $this->targetResolver->resolveTarget($commandMessage);
        $aggregate = $this->repository->load($versionedId->getIdentifier(),
            $versionedId->getVersion());
        
        $reflectionMethod = new \ReflectionMethod($aggregate, $this->methodName);
        $reflectionMethod->invokeArgs($aggregate, array($commandMessage->getPayload()));
    }

    public static function subscribe($className, RepositoryInterface $repository, 
            CommandBusInterface $commandBus)
    {
        $reflClass = new \ReflectionClass($className);
        $reader = new AnnotationReader();
        
        // !!! TODO one reflection scanner
        foreach ($reflClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            $annot = $reader->getMethodAnnotation($method,
                    \Governor\Framework\Annotations\CommandHandler::class);

            // not a handler
            if (null === $annot) {
                continue;
            }
 
            $commandParam = current($method->getParameters());

            // command type must be typehinted
            if (!$commandParam->getClass()) {
                continue;
            }

            $commandClassName = $commandParam->getClass()->name;
            $methodName = $method->name;
            
            $handler = new AnnotatedAggregateCommandHandler($commandClassName, 
                    $methodName, $reflClass->getName(),$repository);      
            
            $commandBus->subscribe($commandClassName, $handler);           
        }
    }
}
