<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Saga;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Description of ContainerAwareResourceInjector
 *
 * @author david
 */
class ContainerAwareResourceInjector implements ResourceInjectorInterface, ContainerAwareInterface
{

    private $container;

    public function injectResources(SagaInterface $saga)
    {
        $saga->setCommandGateway($this->container->get('governor.command_gateway'));
        $saga->setIdentityGenerator($this->container->get('smart_gecko.identity_generator'));
    }

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

}
