<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\EventStore\Odm;

use Doctrine\ODM\MongoDB\DocumentManager;
use Governor\Framework\EventStore\EventStreamNotFoundException;
use Governor\Framework\Domain\GenericDomainEventMessage;
use Governor\Framework\Domain\DomainEventStreamInterface;
use Governor\Framework\Domain\DomainEventMessageInterface;
use Governor\Framework\EventStore\EventStoreInterface;
use Governor\Framework\EventStore\SnapshotEventStoreInterface;
use Governor\Framework\Serializer\SerializerInterface;
use Governor\Framework\Serializer\MessageSerializer;

/**
 * Description of OdmEventStore
 *
 * @author david
 */
class OdmEventStore implements EventStoreInterface, SnapshotEventStoreInterface
{

    private $documentManager;
    private $serializer;

    function __construct(DocumentManager $documentManager,
        SerializerInterface $serializer)
    {
        $this->documentManager = $documentManager;
        $this->serializer = new MessageSerializer($serializer);
    }

    public function appendEvents($type, DomainEventStreamInterface $events)
    {
        
    }

    public function readEvents($type, $identifier)
    {
        
    }

    public function appendSnapshotEvent($type,
        DomainEventMessageInterface $snapshotEvent)
    {
        
    }

}
