<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Domain;

interface EventRegistrationCallbackInterface
{

    /**
     * Invoked when an Aggregate registers an Event for publication. The simplest implementation will simply return
     * the given <code>event</code>.
     *
     * @param event The event registered for publication
     * @param <T>   The type of payload
     * @return the message to actually publish. May <em>not</em> be <code>null</code>.
     */
    public function onRegisteredEvent(DomainEventMessageInterface $event);
}
