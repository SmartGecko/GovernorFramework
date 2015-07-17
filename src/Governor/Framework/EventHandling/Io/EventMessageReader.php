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

namespace Governor\Framework\EventHandling\Io;

use Governor\Framework\Domain\GenericEventMessage;
use Governor\Framework\Domain\GenericDomainEventMessage;
use Governor\Framework\Domain\MetaData;
use Governor\Framework\Serializer\SerializerInterface;
use Governor\Framework\Serializer\SimpleSerializedObject;
use Governor\Framework\Serializer\SimpleSerializedType;
use Governor\Framework\Serializer\MessageSerializer;

/**
 * EventMessageReader converts a binary stream encoded with {@see EventMessageWriter} to the
 * correct EventMessageInterface implementation.
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
class EventMessageReader
{

    /**
     * @var MessageSerializer
     */
    private $serializer;

    /**
     * @param SerializerInterface $serializer Serializer.
     */
    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = new MessageSerializer($serializer);
    }

    /**
     * Reads the data and constructs the suitable EventMessageInterface implementation.
     *
     * @param mixed $data Input data.
     * @return GenericDomainEventMessage|GenericEventMessage
     */
    public function readEventMessage($data)
    {
        $raw = unpack("ntype/a36identifier/Ntimestamp", $data);
        $offset = 42;

        if ($raw['type'] === 3) {
            $raw = array_merge(
                $raw,
                unpack("a36aggregateIdentifier/Nscn", substr($data, $offset))
            );
            $offset += 40;
        }

        $this->read($raw, $offset, $data, "payloadType");
        $this->read($raw, $offset, $data, "payload");
        $this->read($raw, $offset, $data, "meta");

        $serializedPayload = new SimpleSerializedObject(
            $raw['payload'],
            new SimpleSerializedType($raw['payloadType'])
        );
        $serializedMetadata = new SimpleSerializedObject(
            $raw['meta'],
            new SimpleSerializedType(MetaData::class)
        );

        $dateTime = \DateTime::createFromFormat('U', $raw['timestamp']);
        $dateTime->setTimezone(new \DateTimeZone(date_default_timezone_get()));

        if (3 === $raw['type']) {
            return new GenericDomainEventMessage(
                $raw['aggregateIdentifier'],
                $raw['scn'],
                $this->serializer->deserialize($serializedPayload),
                $this->serializer->deserialize($serializedMetadata),
                $raw['identifier'], $dateTime
            );
        } else {
            return new GenericEventMessage(
                $this->serializer->deserialize($serializedPayload),
                $this->serializer->deserialize($serializedMetadata),
                $raw['identifier'], $dateTime
            );
        }
    }

    /**
     * @param mixed $raw
     * @param int $offset
     * @param mixed $data
     * @param string $name
     */
    private function read(&$raw, &$offset, $data, $name)
    {
        $raw = array_merge(
            $raw,
            unpack(sprintf("N%sLength", $name), substr($data, $offset))
        );
        $offset += 4;

        $raw = array_merge(
            $raw,
            unpack(
                sprintf("a%s%s", $raw[sprintf("%sLength", $name)], $name),
                substr($data, $offset)
            )
        );
        $offset += $raw[sprintf("%sLength", $name)];
    }

}
