<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Plugin\SymfonyBundle\DependencyInjection\Compiler;

use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Description of CommandHandlerPass
 *
 * @author david
 */
class CommandHandlerPass extends AbstractHandlerPass
{

    public function process(ContainerBuilder $container)
    {
        $reader = new AnnotationReader();
        $locatorDefinition = $container->findDefinition('governor.command_handler_locator');

        foreach ($container->findTaggedServiceIds('governor.command_handler') as $id => $attributes) {
            $definition = $container->findDefinition($id);
            $class = $definition->getClass();

            $reflClass = new \ReflectionClass($class);

            foreach ($reflClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                $annot = $reader->getMethodAnnotation($method,
                    'Governor\Framework\Annotations\CommandHandler');

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
                $commandTarget = new Reference($id);
                $handlerId = $this->getHandlerIdentifier("governor.command_handler");

                $container->register($handlerId,
                        'Governor\Framework\CommandHandling\Handlers\AnnotatedCommandHandler')
                    ->addArgument($commandClassName)
                    ->addArgument($methodName)
                    ->addArgument($commandTarget)
                    ->setPublic(true);

                $locatorDefinition->addMethodCall('subscribe',
                    array($commandClassName, $handlerId));
            }
        }
    }

}
