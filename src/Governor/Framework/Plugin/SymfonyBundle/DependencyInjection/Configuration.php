<?php

namespace Governor\Framework\Plugin\SymfonyBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('governor');
        
        $rootNode
            ->children()                
                ->arrayNode('aggregate_locations')
                    ->prototype('scalar')->end()
                ->end()
                ->scalarNode('lock_manager')
                    ->defaultValue('null')
                    ->validate()
                    ->ifNotInArray(array('null', 'optimistic', 'pesimistic'))
                        ->thenInvalid('Invalid lock manager driver "%s", possible values are '.
                                       "[\"null\",\"optimistic\",\"pesimistic\"]")
                    ->end()
                ->end()
                ->booleanNode('monolog')->defaultTrue()->end()                    
                    ->arrayNode('repositories')                        
                        ->useAttributeAsKey('name')
                        ->prototype('array')
                            ->children()
                                ->scalarNode('aggregate_root')->isRequired()->end()
                                ->scalarNode('type')
                                    ->validate()
                                    ->ifNotInArray(array('doctrine', 'eventsourcing', 'hybrid'))
                                        ->thenInvalid("Invalid repository type %s, possible values are " . 
                                                      "[\"doctrine\",\"eventsourcing\",\"hybrid\"]")
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end();

        return $treeBuilder;
    }
}
