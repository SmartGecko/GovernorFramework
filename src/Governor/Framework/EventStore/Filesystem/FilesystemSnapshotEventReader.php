<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\EventStore\Filesystem;

use Governor\Framework\EventStore\EventStoreException;
use Governor\Framework\Serializer\SerializerInterface;

/**
 * Description of FileSystemSnapshotEventReader
 *
 * @author david
 */
class FilesystemSnapshotEventReader
{

    private $eventFile;
    private $snapshotEventFile;
    private $eventSerializer;

    /**
     * Creates a snapshot event reader that reads the latest snapshot from the <code>snapshotEventFile</code>.
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
     * Reads the latest snapshot of the given aggregate identifier.
     *
     * @param type       the aggregate's type
     * @param identifier the aggregate's identifier
     * @return The latest snapshot of the given aggregate identifier
     *
     * @throws IOException when reading the <code>snapshotEventFile</code> or reading the <code>eventFile</code> failed
     */
    public function readSnapshotEvent($type, $identifier)
    {
        $snapshotEvent = null;
        $fileSystemSnapshotEvent = $this->readLastSnapshotEntry();

        if (null !== $fileSystemSnapshotEvent) {
            $this->eventFile->fseek($fileSystemSnapshotEvent['bytesToSkip']);
            $actuallySkipped = $this->eventFile->ftell();
            
            if ($actuallySkipped !== $fileSystemSnapshotEvent['bytesToSkip']) {
                throw new EventStoreException(sprintf(
                        "The skip operation did not actually skip the expected amount of bytes. " .
                        "The event log of aggregate of type %s and identifier %s might be corrupt.",
                        $type, $identifier));
            }

            $snapshotEvent = $fileSystemSnapshotEvent['snapshotEvent'];
        }

        return $snapshotEvent;
    }

    private function readLastSnapshotEntry()
    {
        $lastSnapshotEvent = null;

        do {
            $snapshotEvent = $this->readSnapshotEventEntry();

            if (!empty($snapshotEvent)) {
                $lastSnapshotEvent = $snapshotEvent;
            }
        } while (!empty($snapshotEvent));

        return $lastSnapshotEvent;
    }

    private function readSnapshotEventEntry()
    {
        $snapshotEventReader = new FilesystemEventMessageReader($this->snapshotEventFile,
                $this->eventSerializer);

        $bytesToSkip = $this->readLong($this->snapshotEventFile);
        $snapshotEvent = $snapshotEventReader->readEventMessage();

        if (null === $bytesToSkip && null === $snapshotEvent) {
            return array();
        }

        return array('snapshotEvent' => $snapshotEvent, 'bytesToSkip' => $bytesToSkip);
    }

    private function readLong($file)
    {
        $stream = null;
        for ($cc = 0; $cc < 4; $cc++) {
            if ($file->eof()) {
                return null;
            }

            $stream .= $file->fgetc();
        }

        $data = unpack("Nskip", $stream);
        return $data['skip'];
    }

}
