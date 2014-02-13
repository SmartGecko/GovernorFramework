<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\EventHandling;

/**
 * Description of SimpleEventBus
 *
 * @author david
 */
class SimpleEventBus implements EventBusInterface
{

    /**
     *
     * @var \Governor\Framework\EventHandling\EventListenerLocatorInterface
     */
    protected $locator;

    function __construct(EventListenerLocatorInterface $locator)
    {
        $this->locator = $locator;
    }

    public function publish(array $events)
    {
        echo "publish\n";
        foreach ($events as $event) {
            $listeners = $this->locator->getListenersFor($event);
            foreach ($listeners as $listener) {
                echo get_class($listener) . "\n";
                $listener->handle($event);
            }
        }
    }

}
