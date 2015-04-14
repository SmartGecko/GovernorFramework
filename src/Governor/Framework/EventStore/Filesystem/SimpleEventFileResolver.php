<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\EventStore\Filesystem;

class SimpleEventFileResolver implements EventFileResolverInterface
{

    const FILE_EXTENSION_EVENTS = 'events';
    const FILE_EXTENSION_SNAPSHOTS = 'snapshots';

    private $baseDirectory;

    /**
     * 
     * @param string $baseDirectory
     */
    function __construct($baseDirectory)
    {        
        if (!is_dir($baseDirectory)) {
            throw new \InvalidArgumentException(sprintf("%s is not a valid directory",
                    $baseDirectory));
        }

        $this->baseDirectory = $baseDirectory;
    }

    public function eventFileExists($type, $aggregateIdentifier)
    {
        return file_exists($this->getEventFileName($type, $aggregateIdentifier,
                        self::FILE_EXTENSION_EVENTS));
    }

    public function openEventFileForReading($type, $aggregateIdentifier)
    {
        return new \SplFileObject($this->getEventFileName($type,
                        $aggregateIdentifier, self::FILE_EXTENSION_EVENTS), 'rb');
    }

    public function openEventFileForWriting($type, $aggregateIdentifier)
    {        
        return new \SplFileObject($this->getEventFileName($type,
                        $aggregateIdentifier, self::FILE_EXTENSION_EVENTS), 'ab+');
    }

    public function openSnapshotFileForReading($type, $aggregateIdentifier)
    {
        return new \SplFileObject($this->getEventFileName($type,
                        $aggregateIdentifier, self::FILE_EXTENSION_SNAPSHOTS),
                'rb');
    }

    public function openSnapshotFileForWriting($type, $aggregateIdentifier)
    {        
        return new \SplFileObject($this->getEventFileName($type,
                        $aggregateIdentifier, self::FILE_EXTENSION_SNAPSHOTS),
                'ab+');
    }

    public function snapshotFileExists($type, $aggregateIdentifier)
    {
        return file_exists($this->getEventFileName($type, $aggregateIdentifier,
                        self::FILE_EXTENSION_SNAPSHOTS));
    }

    /**
     * @param string $type
     * @param string $identifier
     * @param string $extension
     * @return string
     */
    private function getEventFileName($type, $identifier, $extension)
    {
        $base = join(DIRECTORY_SEPARATOR, array($this->baseDirectory, $type));
        
        if (!file_exists($base) && !mkdir($base)) {
            throw new \RuntimeException (sprintf("Could not create directory %s", $base));
        }

        return join(DIRECTORY_SEPARATOR, array($base, $identifier)) . "." . $extension;
    }

}
