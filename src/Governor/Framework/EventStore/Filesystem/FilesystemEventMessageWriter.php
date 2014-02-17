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
 * @author 255196
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
     * @throws java.io.IOException when any exception occurs writing to the underlying stream
     */
    public function writeEventMessage(DomainEventMessageInterface $eventMessage)
    {                       
        print_r($eventMessage);
        $this->file->fwrite($this->serializer->serialize($eventMessage));
        // type byte for future use
       /* $this->file->fwrite((int) 0);
        $this->file->fwrite($eventMessage->getIdentifier());
        $this->file->fwrite($eventMessage->getTimestamp()->format('c'));
        $this->file->fwrite($eventMessage->getAggregateIdentifier());
        $this->file->fwrite($eventMessage->getScn());

        $serializedPayload = $this->messageSerializer->serializePayload($eventMessage->getPayload());
        $serializedMetaData = $this->messageSerializer->serializeMetaData($eventMessage->getMetaData());
        
        $this->file->fwrite($eventMessage->getPayloadType());
        //   out . writeUTF(serializedPayload . getType() . getName());
        //  String revision = serializedPayload . getType() . getRevision();
        //  out . writeUTF(revision == null ? "" : revision);
        $this->file->fwrite(strlen($serializedPayload));
        $this->file->fwrite($serializedPayload);
        $this->file->fwrite(strlen($serializedMetaData));
        $this->file->fwrite($serializedMetaData);*/
    }

}
