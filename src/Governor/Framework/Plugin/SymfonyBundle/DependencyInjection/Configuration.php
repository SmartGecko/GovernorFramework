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
                ->scalarNode('command_target_resolver')
                    ->defaultValue('annotation')
                    ->validate()
                    ->ifNotInArray(array('annotation', 'metadata'))
                        ->thenInvalid('Invalid command target resolver "%s", possible values are '.
                                       "[\"annotation\",\"metadata\"]")
                    ->end()
                ->end()
                ->scalarNode('lock_manager')
                    ->defaultValue('null')
                    ->validate()
                    ->ifNotInArray(array('null', 'optimistic', 'pesimistic'))
                        ->thenInvalid('Invalid lock manager "%s", possible values are '.
                                       "[\"null\",\"optimistic\",\"pesimistic\"]")
                    ->end()
                ->end()
                ->arrayNode('event_store')
                    ->isRequired()
                    ->children()
                        ->scalarNode('type')
                            ->defaultValue('null')
                            ->validate()
                            ->ifNotInArray(array('null', 'orm', 'odm', 'filesystem'))
                                ->thenInvalid('Invalid event store "%s", possible values are '.
                                           "[\"null\",\"orm\",\"odm\", \"filesystem\"]")
                            ->end()
                        ->end()
                        ->arrayNode('parameters')
                            ->children()
                                ->scalarNode('entity_manager')->end()
                                ->scalarNode('document_manager')->end()
                                ->scalarNode('directory')->end()
                            ->end()
                        ->end()
                    ->end()                    
                ->end()
                ->arrayNode('saga_repository')                    
                    ->children()
                        ->scalarNode('type')
                            ->defaultValue('orm')
                            ->validate()
                            ->ifNotInArray(array('orm', 'odm'))
                                ->thenInvalid('Invalid saga repository "%s", possible values are '.
                                           "[\"orm\",\"odm\"]")
                            ->end()
                        ->end()
                        ->arrayNode('parameters')
                            ->children()
                                ->scalarNode('entity_manager')->end()
                                ->scalarNode('document_manager')->end()                                
                            ->end()
                        ->end()
                    ->end()                    
                ->end()
                ->scalarNode('serializer')
                    ->defaultValue('php')
                    ->validate()
                    ->ifNotInArray(array('php', 'jms'))
                        ->thenInvalid('Invalid serializer "%s", possible values are '.
                                           "[\"php\",\"jms\"]")
                    ->end()
                ->end()     
                ->arrayNode('saga_manager')
                    ->children()
                        ->scalarNode('type')->defaultValue('annotation')->end()
                        ->arrayNode('saga_locations')
                            ->prototype('scalar')->end()
                        ->end()
                    ->end()
                ->end()
                ->append($this->addRepositoriesNode())
                ->append($this->addAggregateCommandHandlersNode())
            ->end();

        return $treeBuilder;
    }

    private function addRepositoriesNode()
    {
        $treeBuilder = new TreeBuilder();
        $node = $treeBuilder->root('repositories');

        $node
            ->requiresAtLeastOneElement()
                ->useAttributeAsKey('name')
                ->prototype('array')
                    ->children()
                        ->scalarNode('aggregate_root')->isRequired()->end()
                        ->scalarNode('type')
                            ->isRequired()
                            ->cannotBeEmpty()
                            ->validate()
                            ->ifNotInArray(array('orm', 'eventsourcing', 'hybrid'))
                                ->thenInvalid("Invalid repository type %s, possible values are " .
                                                      "[\"orm\",\"eventsourcing\",\"hybrid\"]")
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end();

        return $node;
    }

    private function addAggregateCommandHandlersNode()
    {
        $treeBuilder = new TreeBuilder();
        $node = $treeBuilder->root('aggregate_command_handlers');

        $node
            ->useAttributeAsKey('name')
                ->prototype('array')
                    ->children()
                        ->scalarNode('aggregate_root')->isRequired()->end()
                        ->scalarNode('repository')->isRequired()->end()
                        ->scalarNode('command_bus')->defaultValue('governor.command_bus')->end()
                    ->end()
                ->end()
            ->end();

        return $node;
    }
}
