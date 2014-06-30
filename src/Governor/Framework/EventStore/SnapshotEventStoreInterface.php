<?php

/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * The software is based on the Axon Framework project which is
 * licensed under the Apache 2.0 license. For more information on the Axon Framework
 * see <http://www.axonframework.org/>.
 * 
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.governor-framework.org/>.
 */

namespace Governor\Framework\EventStore;

use Governor\Framework\Domain\DomainEventMessageInterface;

/**
 * Description of a snapshot event store that is capable of storing snapshot events.
 * 
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a> 
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
     * @param string $type          The type of aggregate the event belongs to
     * @param DomainEventMessageInterface $snapshotEvent The event summarizing one or more domain events for a specific aggregate.
     */
    public function appendSnapshotEvent($type,
        DomainEventMessageInterface $snapshotEvent);
}
