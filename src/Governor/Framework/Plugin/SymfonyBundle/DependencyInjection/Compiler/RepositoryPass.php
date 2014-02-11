<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Plugin\SymfonyBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Description of RepositoryPass
 *
 * @author david
 */
class RepositoryPass implements CompilerPassInterface
{

    public function process(ContainerBuilder $container)
    {
        foreach ($container->findTaggedServiceIds('governor.repository') as $id => $attributes) {
            $tagAggregateRoot = null;

            foreach ($attributes as $tagAttributes) {
                if (array_key_exists('aggregateRoot', $tagAttributes)) {
                    $tagAggregateRoot = $tagAttributes['aggregateRoot'];
                    break;
                }
            }

            $definition = $container->findDefinition($id);

            if (null === $tagAggregateRoot) {
                throw new \RuntimeException(sprintf("Missing aggregateRoot attribute on the tagged governor repository [%s]",
                    $id));
            }

            // set the first argument based on the entity in the service tag 
            $arguments = $definition->getArguments();
            $arguments[0] = $tagAggregateRoot;

            $definition->setArguments($arguments);
            $container->setDefinition($id, $definition);
        }
    }

}
