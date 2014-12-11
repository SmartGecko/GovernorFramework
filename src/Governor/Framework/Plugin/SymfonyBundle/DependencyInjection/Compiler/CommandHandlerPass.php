<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Plugin\SymfonyBundle\DependencyInjection\Compiler;

use Governor\Framework\Annotations\CommandHandler;
use Governor\Framework\CommandHandling\Handlers\AnnotatedCommandHandler;
use Governor\Framework\Common\Annotation\MethodMessageHandlerInspector;
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
        foreach ($container->findTaggedServiceIds('governor.command_handler') as $id => $attributes) {
            $busDefinition = $container->findDefinition(sprintf("governor.command_bus.%s",
                            isset($attributes['command_bus']) ? $attributes['command_bus']
                                        : 'default'));

            $definition = $container->findDefinition($id);
            $class = $definition->getClass();

            $inspector = new MethodMessageHandlerInspector(new \ReflectionClass($class),
                    CommandHandler::class);

            foreach ($inspector->getHandlerDefinitions() as $handlerDefinition) {
                $handlerId = $this->getHandlerIdentifier("governor.command_handler");

                $container->register($handlerId, AnnotatedCommandHandler::class)
                        ->addArgument($class)
                        ->addArgument($handlerDefinition->getMethod()->name)
                        ->addArgument(new Reference('governor.parameter_resolver_factory'))
                        ->addArgument(new Reference($id))
                        ->setPublic(true)
                        ->setLazy(true);

                $busDefinition->addMethodCall('subscribe',
                        array($handlerDefinition->getPayloadType(), new Reference($handlerId)));
            }
        }
    }

}
