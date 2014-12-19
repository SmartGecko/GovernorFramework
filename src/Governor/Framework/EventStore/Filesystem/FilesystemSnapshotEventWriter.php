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

namespace Governor\Framework\EventStore\Filesystem;

use Governor\Framework\Serializer\SerializerInterface;
use Governor\Framework\Domain\DomainEventMessageInterface;
use Governor\Framework\EventStore\EventStoreException;

/**
 * Description of FilesystemSnapshotEventWriter
 * 
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a> 
 */
class FilesystemSnapshotEventWriter
{

    /**
     * @var \SplFileObject
     */
    private $eventFile;

    /**
     * @var \SplFileObject
     */
    private $snapshotEventFile;

    /**
     * @var SerializerInterface
     */
    private $eventSerializer;

    /**
     * Creates a snapshot event writer that writes any given <code>snapshotEvent</code> to the given
     * <code>snapshotEventFile</code>.
     *
     * @param \SplFileObject $eventFile         used to skip the number of bytes specified by the latest snapshot
     * @param \SplFileObject $snapshotEventFile the file to read snapshots from
     * @param SerializerInterface $eventSerializer   the serializer that is used to deserialize events in snapshot file
     */
    public function __construct(\SplFileObject $eventFile,
            \SplFileObject $snapshotEventFile,
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
     * @param DomainEventMessageInterface $snapshotEvent The snapshot to write to the {@link #snapshotEventFile}
     * @throws EventStoreException
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
     * @param DomainEventMessageInterface $snapshotEvent the snapshot event
     * @return integer the bytes to skip when reading the event file
     *
     * @throws \Exception
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
