<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\CommandHandling\Handlers;

use Governor\Framework\CommandHandling\CommandMessageInterface;
use Governor\Framework\CommandHandling\CommandTargetResolverInterface;
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
        CommandTargetResolverInterface $targetResolver)
    {
        parent::__construct($commandName, $methodName);
        $this->repository = $repository;
        $this->aggregateType = $aggregateType;
        $this->targetResolver = $targetResolver;
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

}
