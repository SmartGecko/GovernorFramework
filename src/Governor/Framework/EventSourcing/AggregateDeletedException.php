<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\EventSourcing;

use Governor\Framework\Repository\AggregateNotFoundException;

/**
 * Description of AggregateDeletedException
 *
 * @author david
 */
class AggregateDeletedException extends AggregateNotFoundException
{

    public function __construct($aggregateIdentifier)
    {
        parent::__construct($aggregateIdentifier,
            sprintf("Aggregate with identifier [%s] not found. It has been deleted.",
                $aggregateIdentifier));
    }

}
