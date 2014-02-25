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
     */
    public function writeEventMessage(DomainEventMessageInterface $eventMessage)
    {
        $serializedPayload = $this->messageSerializer->serializePayload($eventMessage->getPayload());
        $serializedMetaData = $this->messageSerializer->serializeMetaData($eventMessage->getMetaData());

        $packFormat = sprintf("na36Na36NNa%sNa%sNa%s",
                strlen($eventMessage->getPayloadType()),
                strlen($serializedPayload), strlen($serializedMetaData));

        $binary = pack($packFormat, 0, $eventMessage->getIdentifier(),
                $eventMessage->getTimestamp()->format('U'),
                $eventMessage->getAggregateIdentifier(),
                $eventMessage->getScn(),
                strlen($eventMessage->getPayloadType()),
                $eventMessage->getPayloadType(), strlen($serializedPayload),
                $serializedPayload, strlen($serializedMetaData),
                $serializedMetaData);

        $len = pack('n', strlen($binary));

        // !!! TODO error handling
        $this->file->fwrite($len);
        $this->file->fwrite($binary);
        $this->file->fflush();
    }

}
