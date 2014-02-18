<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\EventStore\Filesystem;

use Governor\Framework\Domain\GenericDomainEventMessage;
use Governor\Framework\Serializer\SerializerInterface;

/**
 * Description of FilesystemEventMessageReader
 *
 * @author 255196
 */
class FilesystemEventMessageReader
{

    /**
     * @var \SplFileObject
     */
    private $file;

    /**
     * @var SerializerInterface 
     */
    private $serializer;

    function __construct(\SplFileObject $file, SerializerInterface $serializer)
    {
        $this->file = $file;
        $this->serializer = $serializer;
    }

    public function readEventMessage()
    {
        if (null === $len = $this->readBytes(2)) {
            return null;
        }        
        
        $eventLength = unpack('nlength', $len);

        if (!is_integer($eventLength['length'])) {
            throw new \RuntimeException("Could not determine the length of the stored event");
        }

        $message = $this->readBytes($eventLength['length']);
        $array = unpack('nmagic/a36eventIdentifier/Ntimestamp/a36aggregateIdentifier/Nscn/NpayloadTypeLength',
                $message);

        $offset = 86;
        $array = array_merge($array,
                unpack(sprintf("a%spayloadType/NpayloadLength",
                                $array['payloadTypeLength']),
                        substr($message, $offset)));

        $offset += strlen($array['payloadType']) + 4;
        $array = array_merge($array,
                unpack(sprintf("a%spayload/NmetaDataLength",
                                $array['payloadLength']),
                        substr($message, $offset)));

        $offset += strlen($array['payload']) + 4;
        $array = array_merge($array,
                unpack(sprintf("a%smetaData", $array['metaDataLength']),
                        substr($message, $offset)));

        $payload = $this->serializer->deserialize($array['payload'],
                $array['payloadType']);
        $metadata = $this->serializer->deserialize($array['metaData'],
                'Governor\Framework\Domain\MetaData');

        return new GenericDomainEventMessage($array['aggregateIdentifier'],
                $array['scn'], $payload, $metadata, $array['eventIdentifier'],
                \DateTime::createFromFormat('U', $array['timestamp']));
    }

    private function readBytes($length)
    {
        $stream = null;
        for ($cc = 0; $cc < $length; $cc++) {
            if ($this->file->eof()) {
                return null;
            }
            
            $stream .= $this->file->fgetc();
        }       

        return $stream;
    }

}
