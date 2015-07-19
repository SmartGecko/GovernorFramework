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


use Governor\Framework\Serializer\SerializerInterface;
use Governor\Framework\Serializer\SimpleSerializedObject;
use Governor\Framework\Serializer\SimpleSerializedType;

/**
 * Holds information about the outcome of a distributed command dispatch operation.
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
class ReplyMessage
{
    const NULL = "_null";

    /**
     * @var string
     */
    private $commandIdentifier;
    /**
     * @var bool
     */
    private $success;
    /**
     * @var string
     */
    private $resultType;
    /**
     * @var string
     */
    private $resultRevision;
    /**
     * @var string
     */
    private $serializedResult;

    /**
     * @var mixed
     */
    private $result;

    /**
     * Constructs a message containing a reply to the command with given <code>commandIdentifier</code>, containing
     * either given <code>returnValue</code> or <code>error</code>, which uses the given <code>serializer</code> to
     * deserialize its contents.
     *
     * @param string $commandIdentifier The identifier of the command to which the message is a reply
     * @param SerializerInterface $serializer The serializer to serialize the message contents with
     * @param mixed $returnValue The return value of command process
     * @param bool $success <code>true</code> if the returnValue represents the completion of the command <code>false</code> otherwise
     */
    public function  __construct(
        $commandIdentifier,
        SerializerInterface $serializer,
        $returnValue,
        $success = true
    ) {
        $this->success = $success;
        $this->result = null;

        if (null === $returnValue) {
            $this->result = null;
        } elseif ($returnValue instanceof \Exception) {
            $this->result = $serializer->serialize($returnValue->getMessage());
        } else {
            $this->result = $serializer->serialize($returnValue);
        }

        $this->commandIdentifier = $commandIdentifier;

        if (null !== $this->result) {
            $this->resultType = $this->result->getType()->getName();
            $this->resultRevision = $this->result->getType()->getRevision();
            $this->serializedResult = $this->result->getData();
        }
    }

    /**
     * Returns the returnValue of the command processing. If {@link #isSuccess()} return <code>false</code>, this
     * method returns <code>null</code>. This method also returns <code>null</code> if response processing returned
     * a <code>null</code> value.
     *
     * @return mixed The return value of command processing
     */
    public function getReturnValue()
    {
        if (!$this->success || null === $this->resultType) {
            return null;
        }

        return $this->result;
    }

    /**
     * Returns the error of the command processing. If {@link #isSuccess()} return <code>true</code>, this
     * method returns <code>null</code>.
     *
     * @return \Exception The exception thrown during command processing
     */
    public function getError()
    {
        if ($this->success) {
            return null;
        }

        return $this->result;
    }


    /**
     * Whether the reply message represents a successfully executed command. In this case, successful means that the
     * command's execution did not result in an exception.
     *
     * @return boolean <code>true</code> if this reply contains a return value, <code>false</code> if it contains an error.
     */
    public function isSuccess()
    {
        return $this->success;
    }

    /**
     * @return string
     */
    public function getCommandIdentifier()
    {
        return $this->commandIdentifier;
    }


    /**
     * @return string
     */
    public function toBytes()
    {
        $data = pack('a36n', $this->commandIdentifier, $this->success ? 1 : 0);

        // TODO payload revision
        if (null === $this->resultType) {
            $data .= pack(sprintf('Na%s', strlen(self::NULL)), strlen(self::NULL), self::NULL);
        } else {
            $data .= pack(
                sprintf('Na%sNa%s', strlen($this->resultType), strlen($this->serializedResult)),
                strlen($this->resultType),
                $this->resultType,
                strlen($this->serializedResult),
                $this->serializedResult
            );
        }

        return $data;
    }

    /**
     * @param SerializerInterface $serializer The serialize to deserialize message contents with
     * @param mixed $data
     * @return self
     */
    public static function fromBytes(
        SerializerInterface $serializer,
        $data
    ) {
        $raw = unpack("a36commandIdentifier/nsuccess", $data);
        $isSuccess = $raw['success'] === 1 ? true : false;
        $offset = 36 + 2;

        self::read($raw, $offset, $data, 'resultType');

        if (self::NULL === $raw['resultType']) {
            return new self($raw['commandIdentifier'], $serializer, null, $isSuccess);
        }

        self::read($raw, $offset, $data, 'result');

        $result = $serializer->deserialize(
            new SimpleSerializedObject($raw['result'], new SimpleSerializedType($raw['resultType']))
        );

        return new self($raw['commandIdentifier'], $serializer, $result, $isSuccess);
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