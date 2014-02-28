<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Saga;

use Governor\Framework\Domain\EventMessageInterface;

/**
 * Interface describing the mechanism that resolves Association Values from events. The Association Values are used to
 * find Saga's potentially interested in this Event.
 */
interface AssociationValueResolverInterface
{

    /**
     * Extracts an Association Value from the given <code>event</code>.
     *
     * @param EventMessageInterface $event The event to extract Association Value from
     * @return AssociationValue The Association Value extracted from the Event, or <code>null</code> if none found.
     */
    public function extractAssociationValue(EventMessageInterface $event);
}
