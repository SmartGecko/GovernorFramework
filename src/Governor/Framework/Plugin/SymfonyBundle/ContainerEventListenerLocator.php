<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Plugin\SymfonyBundle;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Governor\Framework\EventHandling\InMemoryEventListenerLocator;
use Governor\Framework\Domain\EventMessageInterface;

/**
 * Description of ContainerEventListenerLocator
 *
 * @author 255196
 */
class ContainerEventListenerLocator extends InMemoryEventListenerLocator implements ContainerAwareInterface
{

    private $container;

    public function getListenersFor(EventMessageInterface $eventName)
    {
      /// !!! TODO
    }

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

}
