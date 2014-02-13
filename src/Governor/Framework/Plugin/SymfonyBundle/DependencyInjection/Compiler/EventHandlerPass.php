<?php

namespace Governor\Framework\Plugin\SymfonyBundle\DependencyInjection\Compiler;

use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class EventHandlerPass extends AbstractHandlerPass
{

    public function process(ContainerBuilder $container)
    {
        $reader = new AnnotationReader();
        $locatorDefinition = $container->findDefinition('governor.event_listener_locator');

        foreach ($container->findTaggedServiceIds('governor.event_handler') as $id => $attributes) {
            $definition = $container->findDefinition($id);
            $class = $definition->getClass();

            $reflClass = new \ReflectionClass($class);

            foreach ($reflClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                $annot = $reader->getMethodAnnotation($method,
                        'Governor\Framework\Annotations\EventHandler');

                // not a handler
                if (null === $annot) {
                    continue;
                }

                $eventParam = current($method->getParameters());

                // event type must be typehinted
                if (!$eventParam->getClass()) {
                    continue;
                }

                $eventClassName = $eventParam->getClass()->name;
                $methodName = $method->name;
                $eventTarget = new Reference($id);
                $handlerId = $handlerId = $this->getHandlerIdentifier("governor.event_handler");

                $container->register($handlerId,
                                'Governor\Framework\EventHandling\Listeners\AnnotatedEventListener')
                        ->addArgument($eventClassName)
                        ->addArgument($methodName)
                        ->addArgument($eventTarget)
                        ->setPublic(true);
                
                $locatorDefinition->addMethodCall('subscribe',
                        array($eventClassName, new Reference($handlerId)));
            }
        }
    }

}
