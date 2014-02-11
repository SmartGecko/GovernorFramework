<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\EventHandling;

use Governor\Framework\Domain\EventMessageInterface;

/**
 *
 * @author david
 */
interface EventListenerInterface
{

    /**
     * Process the given event. The implementation may decide to process or skip the given event. It is highly
     * unrecommended to throw any exception during the event handling process.
     *
     * @param event the event to handle
     */
    public function handle(EventMessageInterface $event);
}
