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

use Governor\Framework\EventStore\EventStoreException;
use Governor\Framework\Serializer\SerializerInterface;
use Governor\Framework\Serializer\SerializedDomainEventDataInterface;

/**
 * Description of FileSystemSnapshotEventReader
 * 
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a> 
 */
class FilesystemSnapshotEventReader
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
     * Creates a snapshot event reader that reads the latest snapshot from the <code>snapshotEventFile</code>.
     *
     * @param \SplFileObject $eventFile         used to skip the number of bytes specified by the latest snapshot
     * @param \SplFileObject $snapshotEventFile the file to read snapshots from
     * @param SerializerInterface $eventSerializer   the serializer that is used to deserialize events in snapshot file
     */
    public function __construct(\SplFileObject $eventFile, \SplFileObject $snapshotEventFile,
            SerializerInterface $eventSerializer)
    {
        $this->eventFile = $eventFile;
        $this->snapshotEventFile = $snapshotEventFile;
        $this->eventSerializer = $eventSerializer;
    }

    /**
     * Reads the latest snapshot of the given aggregate identifier.
     *
     * @param string $type       the aggregate's type
     * @param string $identifier the aggregate's identifier
     * @return SerializedDomainEventDataInterface The latest snapshot of the given aggregate identifier
     *
     * @throws EventStoreException when reading the <code>snapshotEventFile</code> or reading the <code>eventFile</code> failed
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

    /**
     * @param \SplFileObject $file
     * @return int
     */
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
