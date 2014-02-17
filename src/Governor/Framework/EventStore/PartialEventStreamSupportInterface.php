<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\EventStore;

interface PartialStreamSupport
{

    /**
     * Returns a Stream containing events for the aggregate identified by the given {@code type} and {@code
     * identifier}, starting at the event with the given {@code firstSequenceNumber} (included) up to and including the
     * event with given {@code lastSequenceNumber}.
     * If no event with given {@code lastSequenceNumber} exists, the returned stream will simply read until the end of
     * the aggregate's events.
     * <p/>
     * The returned stream will not contain any snapshot events.
     *
     * @param type                The type identifier of the aggregate
     * @param identifier          The identifier of the aggregate
     * @param firstSequenceNumber The sequence number of the first event to find
     * @param lastSequenceNumber  The sequence number of the last event in the stream
     * @return a Stream containing events for the given aggregate, starting at the given first sequence number
     */
    public function readEvents(S$type, $identifier, $firstSequenceNumber,
            $lastSequenceNumber = null);
}
