<?php

/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * The software is based on the Axon Framework project which is
 * licensed under the Apache 2.0 license. For more information on the Axon Framework
 * see <http://www.axonframework.org/>.
 * 
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.governor-framework.org/>.
 */

namespace Governor\Framework\Plugin\SymfonyBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Describes the configuration of the Governor Framework.
 * 
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a> 
 */
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
                ->scalarNode('order_resolver')
                    ->defaultValue('annotation')
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
                    ->children()
                        ->scalarNode('type')                            
                            ->validate()
                            ->ifNotInArray(array('orm', 'odm', 'filesystem'))
                                ->thenInvalid('Invalid event store "%s", possible values are '.
                                           "[\"orm\",\"odm\", \"filesystem\"]")
                            ->end()
                        ->end()
                        ->arrayNode('parameters')
                            ->children()
                                ->scalarNode('entity_manager')->end()
                                ->scalarNode('entry_store')->end()
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
                    ->defaultValue('jms')
                    ->validate()
                    ->ifNotInArray(array('jms'))
                        ->thenInvalid('Invalid serializer "%s", possible values are '.
                                           "[\"jms\"]")
                    ->end()
                ->end()
                ->arrayNode('saga_manager')
                    ->children()
                        ->scalarNode('type')->defaultValue('annotation')->end()
                        ->scalarNode('event_bus')->defaultValue('default')->end()
                        ->arrayNode('saga_locations')
                            ->prototype('scalar')->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('cluster_selector')
                    ->children()
                        ->scalarNode('class')->isRequired()->end()
                        ->scalarNode('cluster')->defaultValue('default')->end()
                    ->end()
                ->end()
                ->append($this->addRepositoriesNode())
                ->append($this->addAggregateCommandHandlersNode())
                ->append($this->addCommandBusesNode())
                ->append($this->addEventBusesNode())
                ->append($this->addCommandGatewaysNode())
                ->append($this->addAMQPTerminalsNode())
                ->append($this->addClustersNode())
            ->end();

        return $treeBuilder;
    }

    private function addClustersNode()
    {
        $treeBuilder = new TreeBuilder();
        $node = $treeBuilder->root('clusters');
        
        $node->useAttributeAsKey('name')
            ->prototype('array')
                ->children()
                    ->scalarNode('class')->isRequired()->end()
                    ->scalarNode('order_resolver')->isRequired()->end()
                ->end()
            ->end();

        return $node;
    }
    
    private function addCommandBusesNode()
    {
        $treeBuilder = new TreeBuilder();
        $node = $treeBuilder->root('command_buses');

        $node->useAttributeAsKey('name')
            ->prototype('array')
                ->children()
                    ->scalarNode('class')->isRequired()->end()
                    ->arrayNode('handler_interceptors')
                        ->prototype('scalar')->end()
                    ->end()
                    ->arrayNode('dispatch_interceptors')
                        ->prototype('scalar')->end()
                    ->end()
                ->end()
            ->end();

        return $node;
    }

    private function addEventBusesNode()
    {
        $treeBuilder = new TreeBuilder();
        $node = $treeBuilder->root('event_buses');

        $node
            ->useAttributeAsKey('name')
            ->prototype('array')
                ->children()
                    ->scalarNode('class')->isRequired()->end()
                    ->scalarNode('terminal')->end()
                ->end()
            ->end();

        return $node;
    }

    private function addCommandGatewaysNode()
    {
        $treeBuilder = new TreeBuilder();
        $node = $treeBuilder->root('command_gateways');

        $node
            ->useAttributeAsKey('name')
            ->prototype('array')
                ->children()
                    ->scalarNode('class')->isRequired()->end()
                    ->scalarNode('command_bus')->defaultValue('default')->end()
                ->end()
            ->end();

        return $node;
    }

    private function addAMQPTerminalsNode()
    {
        $treeBuilder = new TreeBuilder();
        $node = $treeBuilder->root('amqp_terminals');
        
        $node
            ->canBeUnset()
                ->useAttributeAsKey('name')
                ->prototype('array')
                    ->children()
                        ->arrayNode('connection')
                            ->children()
                                ->scalarNode('host')->defaultValue('localhost')->end()
                                ->scalarNode('port')->defaultValue(5672)->end()
                                ->scalarNode('user')->defaultValue('guest')->end()
                                ->scalarNode('password')->defaultValue('guest')->end()
                                ->scalarNode('vhost')->defaultValue('/')->end()
                            ->end()
                        ->end()
                        ->scalarNode('routing_key_resolver')->isRequired()->end()
                    ->end()
                ->end()
            ->end();
        
        return $node;
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
                        ->scalarNode('event_bus')->defaultValue('default')->end()
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
                     //   ->arrayNode('parameters')
                       //     ->children()
                         //       ->scalarNode('entity_manager')->end()
                           //     ->scalarNode('document_manager')->end()
                           // ->end()
                        //->end()
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
                        ->scalarNode('command_bus')->defaultValue('default')->end()
                    ->end()
                ->end()
            ->end();

        return $node;
    }
}
