<?php

namespace Governor\Framework\Plugin\SymfonyBundle\DependencyInjection\Compiler;

use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class HandlerPass implements CompilerPassInterface
{

    public function process(ContainerBuilder $container)
    {
        $this->registerCommandHandlers($container);
        $this->registerEventHandlers($container);
    }

    private function registerCommandHandlers($container)
    {
        $services = array();
        $reader = new AnnotationReader();
        $locatorDefinition = $container->findDefinition('command_bus');

        foreach ($container->findTaggedServiceIds('governor.command_handler') as $id => $attributes) {
            $definition = $container->findDefinition($id);
            $class = $definition->getClass();

            $reflClass = new \ReflectionClass($class);
            
            $handlerId = hash('crc32', openssl_random_pseudo_bytes(8));

            $def = $container->register($handlerId,
                    'Governor\Framework\CommandHandling\AnnotatedCommandHandlerAdapter')
                ->addArgument(new Reference($id))
                ->setPublic(true);

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

                $services[$commandParam->getClass()->name] = $handlerId;
            }

            $container->addDefinitions(array($def));
        }

        $locatorDefinition->addMethodCall('setSubscriptions', array($services));
    }

    function registerEventHandlers($container)
    {
        $services = array();
        foreach ($container->findTaggedServiceIds('governor.event_handler') as $id => $attributes) {
            $definition = $container->findDefinition($id);
            $class = $definition->getClass();

            $reflClass = new \ReflectionClass($class);
            foreach ($reflClass->getMethods() as $method) {
                if ($method->getNumberOfParameters() != 1) {
                    continue;
                }

                $methodName = $method->getName();
                if (strpos($methodName, "on") !== 0) {
                    continue;
                }

                $eventName = strtolower(substr($methodName, 2));

                if (!isset($services[$eventName])) {
                    $services[$eventName] = array();
                }

                $services[$eventName][] = $id;
            }
        }

        $locatorDefinition = $container->findDefinition('governor.container_handler_locator');
        $locatorDefinition->addMethodCall('registerEventHandlers',
            array($services));
    }

}
