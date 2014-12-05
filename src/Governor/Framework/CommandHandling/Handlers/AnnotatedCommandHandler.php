<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\CommandHandling\Handlers;

use Governor\Framework\CommandHandling\CommandMessageInterface;
use Governor\Framework\UnitOfWork\UnitOfWorkInterface;
use Governor\Framework\Common\Annotation\MethodMessageHandler;
use Governor\Framework\Common\ParameterResolverFactoryInterface;

/**
 * Description of GenericCommandHandler
 *
 * @author david
 */
class AnnotatedCommandHandler extends AbstractAnnotatedCommandHandler
{

    /**
     * @var mixed
     */
    private $target;

    public function __construct(\ReflectionMethod $method, array $annotations,
            ParameterResolverFactoryInterface $parameterResolver, $target)
    {
        parent::__construct($method, $annotations, $parameterResolver);
        $this->target = $target;
    }

    public function handle(CommandMessageInterface $commandMessage,
            UnitOfWorkInterface $unitOfWork)
    {
        $arguments = $this->resolveArguments($commandMessage);

        return $this->getMethod()->invokeArgs($this->target, $arguments);
    }

}
