<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\EventStore\Filesystem;

use Governor\Framework\Domain\DomainEventMessageInterface;
use Governor\Framework\Serializer\SerializerInterface;
use Governor\Framework\Serializer\MessageSerializer;

/**
 * Description of FilesystemEventMessageWriter
 *
 */
class FilesystemEventMessageWriter
{

    private $messageSerializer;
    private $file;

    /**
     * Creates a new EventMessageWriter writing data to the specified underlying <code>output</code>.
     *
     * @param output     the underlying output
     * @param serializer The serializer to deserialize payload and metadata with
     */
    public function __construct(\SplFileObject $file,
            SerializerInterface $serializer)
    {
        $this->file = $file;
        $this->messageSerializer = new MessageSerializer($serializer);
        $this->serializer = $serializer;
    }

    /**
     * Writes the given <code>eventMessage</code> to the underling output.
     *
     * @param eventMessage the EventMessage to write to the underlying output     
     */
    public function writeEventMessage(DomainEventMessageInterface $eventMessage)
    {               
        $serializedPayload = $this->messageSerializer->serializePayload($eventMessage);        
        $serializedMetaData = $this->messageSerializer->serializeMetaData($eventMessage);
        
        $packFormat = sprintf("na36Na36NNa%sNa%sNa%s",
                strlen($serializedPayload->getType()->getName()),
                strlen($serializedPayload->getData()), strlen($serializedMetaData->getData()));
        
        $binary = pack($packFormat, 0, $eventMessage->getIdentifier(),
                $eventMessage->getTimestamp()->format('U'),
                $eventMessage->getAggregateIdentifier(),
                $eventMessage->getScn(),
                strlen($serializedPayload->getType()->getName()),
                $serializedPayload->getType()->getName(), strlen($serializedPayload->getData()),
                $serializedPayload->getData(), strlen($serializedMetaData->getData()),
                $serializedMetaData->getData());

        $len = pack('n', strlen($binary));
        
        // !!! TODO error handling
        $this->file->fwrite($len);
        $this->file->fwrite($binary);        
        $this->file->fflush();
    }

}
