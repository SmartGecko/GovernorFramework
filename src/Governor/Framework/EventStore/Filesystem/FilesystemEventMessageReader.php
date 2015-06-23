<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\EventStore\Filesystem;

use Governor\Framework\Serializer\SimpleSerializedDomainEventData;
use Governor\Framework\Serializer\SerializerInterface;

/**
 * Description of FilesystemEventMessageReader
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>  *
 */
class FilesystemEventMessageReader
{

    /**
     * @var \SplFileObject
     */
    private $file;

    function __construct(\SplFileObject $file)
    {
        $this->file = $file;
    }

    /**
     *
     * @return \Governor\Framework\Serializer\SerializedDomainEventDataInterface|null
     * @throws \RuntimeException
     */
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
        $array = unpack(
            'nmagic/a36eventIdentifier/Ntimestamp/a36aggregateIdentifier/Nscn/NpayloadTypeLength',
            $message
        );

        $offset = 86;
        $array = array_merge(
            $array,
            unpack(
                sprintf(
                    "a%spayloadType/NpayloadLength",
                    $array['payloadTypeLength']
                ),
                substr($message, $offset)
            )
        );

        $offset += strlen($array['payloadType']) + 4;
        $array = array_merge(
            $array,
            unpack(
                sprintf("a%spayload/NmetaDataLength", $array['payloadLength']),
                substr($message, $offset)
            )
        );

        $offset += strlen($array['payload']) + 4;
        $array = array_merge(
            $array,
            unpack(
                sprintf("a%smetaData", $array['metaDataLength']),
                substr($message, $offset)
            )
        );

        // !!! TODO support for payload revision 
        return new SimpleSerializedDomainEventData(
            $array['eventIdentifier'],
            $array['aggregateIdentifier'], $array['scn'],
            \DateTime::createFromFormat('U', $array['timestamp']),
            $array['payloadType'], null, $array['payload'], $array['metaData']
        );
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
