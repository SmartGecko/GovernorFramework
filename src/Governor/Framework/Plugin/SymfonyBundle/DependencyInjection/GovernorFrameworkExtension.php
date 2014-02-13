<?php

namespace Governor\Framework\Plugin\SymfonyBundle\DependencyInjection;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Doctrine\Common\Annotations\AnnotationReader;

class GovernorFrameworkExtension extends Extension
{

    public function load(array $configs, ContainerBuilder $container)
    {
        $config = $this->processConfiguration(new Configuration, $configs);

        $container->setAlias('governor.lock_manager',
            new Alias(sprintf('governor.lock_manager.%s',
                $config['lock_manager'])));

        $container->setAlias('governor.command_target_resolver',
            new Alias(sprintf('governor.command_target_resolver.%s',
                $config['command_target_resolver'])));

        $loader = new XmlFileLoader($container,
            new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');

        $container->setParameter('governor.aggregate_locations',
            $config['aggregate_locations']);

        // configure repositories 
        $this->loadRepositories($config, $container);
        //configure aggregate command handlers
        $this->loadAggregateCommandHandlers($config, $container);
    }

    private function loadAggregateCommandHandlers($config,
        ContainerBuilder $container)
    {
        $reader = new AnnotationReader();
        $busDefinition = $container->findDefinition('governor.command_bus');

        foreach ($config['aggregate_command_handlers'] as $name => $parameters) {
            $reflectionClass = new \ReflectionClass($parameters['aggregate_root']);

            foreach ($reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
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

                $handlerClass = 'Governor\Framework\CommandHandling\Handlers\AnnotatedAggregateCommandHandler';
                $methodName = $method->name;
                $commandClassName = $commandParam->getClass()->name;
                $repository = new Reference($parameters['repository']);
                $resolver = new Reference('governor.command_target_resolver');

                $handlerId = sprintf("governor.aggregate_command_handler.%s",
                    hash('crc32', openssl_random_pseudo_bytes(8)));

                $container->register($handlerId, $handlerClass)
                    ->addArgument($commandClassName)
                    ->addArgument($methodName)
                    ->addArgument($parameters['aggregate_root'])
                    ->addArgument($repository)
                    ->addArgument($resolver)
                    ->setPublic(true)
                    ->setLazy(true);

                $busDefinition->addMethodCall('subscribe',
                    array($commandParam->getClass()->name, new Reference($handlerId)));
            }
        }
    }

    private function loadRepositories($config, ContainerBuilder $container)
    {
        foreach ($config['repositories'] as $name => $parameters) {
            $repository = new DefinitionDecorator(sprintf('governor.repository.%s',
                    $parameters['type']));
            $repository->replaceArgument(0, $parameters['aggregate_root'])
                ->setPublic(true);

            $container->setDefinition(sprintf('%s.repository', $name),
                $repository);
        }
    }

}
