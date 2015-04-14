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

use Governor\Framework\Domain\DomainEventMessageInterface;
use Governor\Framework\Serializer\SerializerInterface;
use Governor\Framework\Serializer\MessageSerializer;

/**
 * Description of FilesystemEventMessageWriter
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
class FilesystemEventMessageWriter
{

    private $messageSerializer;
    private $file;

    /**
     * Creates a new EventMessageWriter writing data to the specified underlying <code>output</code>.
     *
     * @param \SplFileObject $file the underlying output file
     * @param SerializerInterface $serializer The serializer to deserialize payload and metadata with
     */
    public function __construct(\SplFileObject $file,
            SerializerInterface $serializer)
    {
        $this->file = $file;
        $this->messageSerializer = new MessageSerializer($serializer);
        $this->serializer = $serializer;
    }

    /**
     * Writes the given <code>eventMessage</code> to the underling output.
     *
     * @param DomainEventMessageInterface $eventMessage the EventMessage to write to the underlying output
     */
    public function writeEventMessage(DomainEventMessageInterface $eventMessage)
    {               
        $serializedPayload = $this->messageSerializer->serializePayload($eventMessage);        
        $serializedMetaData = $this->messageSerializer->serializeMetaData($eventMessage);
        
        $packFormat = sprintf("na36Na36NNa%sNa%sNa%s",
                strlen($serializedPayload->getType()->getName()),
                strlen($serializedPayload->getData()), strlen($serializedMetaData->getData()));
        
        $binary = pack($packFormat, 0, $eventMessage->getIdentifier(),
                $eventMessage->getTimestamp()->format('U'),
                $eventMessage->getAggregateIdentifier(),
                $eventMessage->getScn(),
                strlen($serializedPayload->getType()->getName()),
                $serializedPayload->getType()->getName(), strlen($serializedPayload->getData()),
                $serializedPayload->getData(), strlen($serializedMetaData->getData()),
                $serializedMetaData->getData());

        $len = pack('n', strlen($binary));
        
        // !!! TODO error handling
        $this->file->fwrite($len);
        $this->file->fwrite($binary);        
        $this->file->fflush();
    }

}
