<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\EventStore\Filesystem;

use Governor\Framework\Serializer\SerializerInterface;
use Governor\Framework\Domain\DomainEventMessageInterface;
use Governor\Framework\EventStore\EventStoreException;

/**
 * Description of FilesystemSnapshotEventWriter
 *
 * @author 255196
 */
class FilesystemSnapshotEventWriter
{

    private $eventFile;
    private $snapshotEventFile;
    private $eventSerializer;

    /**
     * Creates a snapshot event writer that writes any given <code>snapshotEvent</code> to the given
     * <code>snapshotEventFile</code>.
     *
     * @param eventFile         used to skip the number of bytes specified by the latest snapshot
     * @param snapshotEventFile the file to read snapshots from
     * @param eventSerializer   the serializer that is used to deserialize events in snapshot file
     */
    public function __construct($eventFile, $snapshotEventFile,
            SerializerInterface $eventSerializer)
    {
        $this->eventFile = $eventFile;
        $this->snapshotEventFile = $snapshotEventFile;
        $this->eventSerializer = $eventSerializer;
    }

    /**
     * Writes the given snapshotEvent to the {@link #snapshotEventFile}.
     * Prepends a long value to the event in the file indicating the bytes to skip when reading the {@link #eventFile}.
     *
     * @param snapshotEvent The snapshot to write to the {@link #snapshotEventFile}
     */
    public function writeSnapshotEvent(DomainEventMessageInterface $snapshotEvent)
    {
        try {            
            $offset = $this->calculateOffset($snapshotEvent);
            $this->snapshotEventFile->fwrite(pack("N", $offset));
            
            $eventMessageWriter = new FilesystemEventMessageWriter($this->snapshotEventFile,
                    $this->eventSerializer);
           
            $eventMessageWriter->writeEventMessage($snapshotEvent);             
        } catch (\Exception $ex) {
            throw new EventStoreException("Error writing a snapshot event", 0,
            $ex);
        }
    }

    /**
     * Calculate the bytes to skip when reading the event file.
     *
     * @param snapshotEvent the snapshot event
     * @return the bytes to skip when reading the event file
     *
     * @throws IOException when the {@link #eventFile} was closed unexpectedly
     */
    private function calculateOffset(DomainEventMessageInterface $snapshotEvent)
    {
        try {
            $eventMessageReader = new FilesystemEventMessageReader($this->eventFile,
                    $this->eventSerializer);

            $lastReadSequenceNumber = -1;                        
            while ($lastReadSequenceNumber < $snapshotEvent->getScn()) {
                $entry = $eventMessageReader->readEventMessage();
                $lastReadSequenceNumber = $entry->getScn();                              
            }
           
            return $this->eventFile->ftell();
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

}
