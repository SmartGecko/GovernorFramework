<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\EventStore;

/**
 * Description of NullEventStore
 *
 * @author david
 */
class NullEventStore implements EventStoreInterface
{
    public function appendEvents($type,
        \Governor\Framework\Domain\DomainEventStreamInterface $events)
    {
        
    }

    public function readEvents($type, $identifier)
    {
        
    }


}
