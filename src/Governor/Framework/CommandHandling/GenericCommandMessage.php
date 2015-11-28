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

namespace Governor\Framework\CommandHandling;

use Ramsey\Uuid\Uuid;
use Governor\Framework\Domain\MetaData;

/**
 * Description of GenericCommandMessage
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
class GenericCommandMessage implements CommandMessageInterface
{

    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $commandName;

    /**
     * @var mixed
     */
    private $payload;

    /**
     * @var MetaData
     */
    private $metaData;

    /**
     * @param mixed $payload
     * @param MetaData $metaData
     * @param string|null $id
     * @param string|null $commandName
     */
    public function __construct(
        $payload,
        MetaData $metaData = null,
        $id = null,
        $commandName = null
    ) {
        $this->id = (null === $id) ? Uuid::uuid1()->toString() : $id;
        $this->commandName = (null === $commandName) ? get_class($payload) : $commandName;
        $this->payload = $payload;
        $this->metaData = (null === $metaData) ? MetaData::emptyInstance() : $metaData;
    }

    /**
     * @param $command
     * @return GenericCommandMessage
     */
    public static function asCommandMessage($command)
    {
        if (!is_object($command)) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Command payload must be an object, but is of type \"%s\"",
                    gettype($command)
                )
            );
        }

        if ($command instanceof CommandMessageInterface) {
            return $command;
        }

        return new GenericCommandMessage($command, MetaData::emptyInstance());
    }

    /**
     * @param array $metadata
     * @return GenericCommandMessage
     */
    public function andMetaData(array $metadata = [])
    {
        if (empty($metadata)) {
            return $this;
        }

        return new GenericCommandMessage(
            $this->payload,
            $this->metaData->mergeWith($metadata), $this->id,
            $this->commandName
        );
    }

    /**
     * @return string
     */
    public function getCommandName()
    {
        return $this->commandName;
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return $this->id;
    }

    /**
     * @return MetaData
     */
    public function getMetaData()
    {
        return $this->metaData;
    }

    /**
     * @return mixed
     */
    public function getPayload()
    {
        return $this->payload;
    }

    /**
     * @return string
     */
    public function getPayloadType()
    {
        return get_class($this->payload);
    }

    /**
     * @param array $metadata
     * @return GenericCommandMessage
     */
    public function withMetaData(array $metadata = [])
    {
        if ($this->metaData->isEqualTo($metadata)) {
            return $this;
        }

        return new GenericCommandMessage(
            $this->payload,
            new MetaData($metadata), $this->id, $this->commandName
        );
    }

}
