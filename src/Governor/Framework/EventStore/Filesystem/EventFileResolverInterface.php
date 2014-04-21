<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\EventStore\Filesystem;

/**
 * Interface that allows basic access to files storing event logs for aggregates.
 * <p/> 
 * 
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a> 
 */
interface EventFileResolverInterface
{

    /**
     * Provides an output stream to the (regular) events file for the aggregate with the given
     * <code>aggregateIdentifier</code> and of given <code>type</code>. Written bytes are appended to already existing
     * information.
     * <p/>
     * The caller of this method is responsible for closing the output stream when all data has been written to it.
     *
     * @param string $type                The type of aggregate to open the stream for
     * @param mixed $aggregateIdentifier the identifier of the aggregate
     * @return \SplFileObject 
     */
    public function openEventFileForWriting($type, $aggregateIdentifier);

    /**
     * Provides an output stream to the snapshot events file for the aggregate with the given
     * <code>aggregateIdentifier</code> and of given <code>type</code>. Written bytes are appended to already existing
     * information.
     * <p/>
     * The caller of this method is responsible for closing the output stream when all data has been written to it.
     *
     * @param string $type                The type of aggregate to open the stream for
     * @param mixed $aggregateIdentifier the identifier of the aggregate
     * @return \SplFileObject 
     */
    public function openSnapshotFileForWriting($type, $aggregateIdentifier);

    /**
     * Provides an input stream to the (regular) events file for the aggregate with the given
     * <code>aggregateIdentifier</code> and of given <code>type</code>.
     * <p/>
     * The caller of this method is responsible for closing the input stream when done reading from it.
     *
     * @param string $type                The type of aggregate to open the stream for
     * @param mixed $aggregateIdentifier the identifier of the aggregate
     * @return \SplFileObject 
     */
    public function openEventFileForReading($type, $aggregateIdentifier);

    /**
     * Provides an input stream to the snapshot events file for the aggregate with the given
     * <code>aggregateIdentifier</code> and of given <code>type</code>.
     * <p/>
     * The caller of this method is responsible for closing the input stream when done reading from it.
     *
     * @param string $type                The type of aggregate to open the stream for
     * @param mixed $aggregateIdentifier the identifier of the aggregate
     * @return \SplFileObject 
     */
    public function openSnapshotFileForReading($type, $aggregateIdentifier);

    /**
     * Indicates whether there is a file containing (regular) events for the given <code>aggregateIdentifier</code> of
     * given <code>type</code>.
     *
     * @param string $type                The type of aggregate
     * @param mixed $aggregateIdentifier the identifier of the aggregate
     * @return boolean <code>true</code> if an event log exists for the aggregate, <code>false</code> otherwise.
     */
    public function eventFileExists($type, $aggregateIdentifier);

    /**
     * Indicates whether there is a file containing snapshot events for the given <code>aggregateIdentifier</code> of
     * given <code>type</code>.
     *
     * @param string $type                The type of aggregate
     * @param mixed $aggregateIdentifier the identifier of the aggregate
     * @return boolean <code>true</code> if a snapshot event log exists for the aggregate, <code>false</code> otherwise.
     */
    public function snapshotFileExists($type, $aggregateIdentifier);
}
