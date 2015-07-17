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

namespace Governor\Framework\CommandHandling\Distributed;


use Governor\Framework\CommandHandling\CommandMessageInterface;
use Governor\Framework\CommandHandling\GenericCommandMessage;
use Governor\Framework\Domain\MetaData;
use Governor\Framework\Serializer\SerializerInterface;
use Governor\Framework\Serializer\MessageSerializer;
use Governor\Framework\Serializer\SimpleSerializedObject;
use Governor\Framework\Serializer\SimpleSerializedType;

class DispatchMessage
{
    /**
     * @var string
     */
    private $commandName;
    /**
     * @var string
     */
    private $commandIdentifier;

    /**
     * @var bool
     */
    private $expectReply;

    /**
     * @var string
     */
    private $payloadType;
    /**
     * @var string
     */
    private $payloadRevision;
    /**
     * @var string
     */

    private $serializedPayload;
    /**
     * @var string
     */
    private $serializedMetaData;

    /**
     * @var CommandMessageInterface
     */
    private $commandMessage;

    /**
     * @param CommandMessageInterface $commandMessage
     * @param SerializerInterface $serializer
     * @param bool $expectReply
     */
    public function __construct(CommandMessageInterface $commandMessage, SerializerInterface $serializer, $expectReply)
    {
        $this->commandMessage = $commandMessage;
        $this->commandIdentifier = $commandMessage->getIdentifier();
        $this->expectReply = $expectReply;

        $messageSerializer = new MessageSerializer($serializer);

        $payload = $messageSerializer->serializePayload($commandMessage);
        $metaData = $messageSerializer->serializeMetaData($commandMessage);

        $this->payloadType = $payload->getType()->getName();
        $this->payloadRevision = $payload->getType()->getRevision();

        $this->serializedPayload = $payload->getData();
        $this->serializedMetaData = $metaData->getData();
        $this->commandName = $commandMessage->getCommandName();
    }

    /**
     * @return string
     */
    public function getCommandIdentifier()
    {
        return $this->commandIdentifier;
    }

    /**
     * @return boolean
     */
    public function isExpectReply()
    {
        return $this->expectReply;
    }

    /**
     * Returns the CommandMessage wrapped in this Message.
     *
     * @return CommandMessageInterface the CommandMessage wrapped in this Message
     */
    public function getCommandMessage()
    {
        return $this->commandMessage;
    }

    /**
     * @return string
     */
    public function toBytes()
    {
        $data = pack(
            sprintf("Na%sa36n", strlen($this->commandName)),
            strlen($this->commandName),
            $this->commandName,
            $this->commandIdentifier,
            $this->expectReply ? 1 : 0
        );

        // TODO payload revision
        $packFormat = sprintf(
            "Na%sNa%sNa%s",
            strlen($this->payloadType),
            strlen($this->serializedPayload),
            strlen($this->serializedMetaData)
        );

        $data .= pack(
            $packFormat,
            strlen($this->payloadType),
            $this->payloadType,
            strlen($this->serializedPayload),
            $this->serializedPayload,
            strlen($this->serializedMetaData),
            $this->serializedMetaData
        );

        return $data;
    }

    /**
     * @param SerializerInterface $serializer The serialize to deserialize message contents with
     * @param mixed $data
     * @return self
     */
    public static function fromBytes(SerializerInterface $serializer, $data)
    {
        $raw = unpack("NcommandNameLength", $data);
        $offset = 4;

        $raw = array_merge(
            $raw,
            unpack(
                sprintf("a%scommandName/a36commandIdentifier/nexpectReply", $raw['commandNameLength']),
                substr($data, $offset)
            )
        );
        $offset += $raw['commandNameLength'] + 36 + 2;

        self::read($raw, $offset, $data, "payloadType");
        self::read($raw, $offset, $data, "payload");
        self::read($raw, $offset, $data, "meta");

        $payload = $serializer->deserialize(
            new SimpleSerializedObject($raw['payload'], new SimpleSerializedType($raw['payloadType']))
        );
        $metaData = $serializer->deserialize(
            new SimpleSerializedObject($raw['meta'], new SimpleSerializedType(MetaData::class))
        );

        return new self(
            new GenericCommandMessage($payload, $metaData, $raw['commandIdentifier'], $raw['commandName']),
            $serializer,
            $raw['expectReply'] === 1 ? true : false
        );
    }

    /**
     * @param mixed $raw
     * @param int $offset
     * @param mixed $data
     * @param string $name
     */
    private static function read(&$raw, &$offset, $data, $name)
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