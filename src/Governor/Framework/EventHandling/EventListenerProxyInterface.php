<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\EventHandling;

/**
 * Specialist interface for implementations of an event listener that redirect actual processing to another instance.
 */
interface EventListenerProxyInterface extends EventListenerInterface {

    /**
     * Returns the instance type that this proxy delegates all event handling to.
     *
     * @return the instance type that this proxy delegates all event handling to
     */
    public function getTargetType();
}