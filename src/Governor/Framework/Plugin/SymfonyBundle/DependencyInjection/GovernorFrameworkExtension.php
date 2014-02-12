<?php

namespace Governor\Framework\Plugin\SymfonyBundle\DependencyInjection;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class GovernorFrameworkExtension extends Extension
{

    public function load(array $configs, ContainerBuilder $container)
    {
        $config = $this->processConfiguration(new Configuration, $configs);

        $container->setAlias('governor.lock_manager', new Alias(sprintf('governor.lock_manager.%s', $config['lock_manager'])));

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');

        $container->setParameter('governor.aggregate_locations', $config['aggregate_locations']);

        // configure repositories 
        $this->loadRepositories($config, $container);
        //configure aggregate command handlers
        $this->loadAggregateCommandHandlers($config, $container);
    }

    private function loadAggregateCommandHandlers($config, ContainerBuilder $container)
    {
        foreach ($config['aggregate_command_handlers'] as $name => $parameters) {
            echo $name . "\n";
            print_r($parameters);
        }
    }

    private function loadRepositories($config, ContainerBuilder $container)
    {
        foreach ($config['repositories'] as $name => $parameters) {
            $repository = new DefinitionDecorator(sprintf('governor.repository.%s', $parameters['type']));
            $repository->replaceArgument(0, $parameters['aggregate_root']);

            $container->setDefinition(sprintf('%s.repository', $name), $repository);
        }
    }

}
