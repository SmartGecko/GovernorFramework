<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\EventStore;

use Governor\Framework\Domain\DomainEventMessageInterface;

/**
 *
 * @author david
 */
interface SnapshotEventStoreInterface extends EventStoreInterface
{

    /**
     * Append the given <code>snapshotEvent</code> to the snapshot event log for the given type <code>type</code>. The
     * sequence number of the <code>snapshotEvent</code> must be equal to the sequence number of the last regular
     * domain
     * event that is included in the snapshot.
     * <p/>
     * Implementations may choose to prune snapshots upon appending a new snapshot, in order to minimize storage space.
     *
     * @param type          The type of aggregate the event belongs to
     * @param snapshotEvent The event summarizing one or more domain events for a specific aggregate.
     */
    public function appendSnapshotEvent($type,
        DomainEventMessageInterface $snapshotEvent);
}
