<?php

namespace Governor\Framework\Plugin\SymfonyBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $tb = new TreeBuilder();

        $tb
            ->root('governor')
                ->children()
                    ->arrayNode('aggregate_locations')                        
                        ->prototype('scalar')->end()
                    ->end()
                    ->booleanNode('monolog')->defaultTrue()->end()
                ->end();

        return $tb;
    }
}
