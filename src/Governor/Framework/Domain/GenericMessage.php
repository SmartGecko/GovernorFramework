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

namespace Governor\Framework\Domain;

use Ramsey\Uuid\Uuid;

/**
 * Basic implementation of the @see MessageInterface.
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
class GenericMessage implements MessageInterface
{
    /**
     * @var string
     */
    private $id;
    /**
     * @var MetaData
     */
    private $metadata;

    /**
     * @var mixed
     */
    private $payload;

    /**
     * @param mixed $payload
     * @param MetaData $metadata
     * @param string $id
     */
    public function __construct($payload, MetaData $metadata = null, $id = null)
    {
        if (!is_object($payload)) {
            throw new \InvalidArgumentException("Payload needs to be an object.");
        }

        $this->id = isset($id) ? $id : Uuid::uuid1()->toString();
        $this->metadata = isset($metadata) ? $metadata : MetaData::emptyInstance();
        $this->payload = $payload;
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return $this->id;
    }

    /**
     *
     * @return \Governor\Framework\Domain\MetaData
     */
    public function getMetaData()
    {
        return $this->metadata;
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
     * @return GenericMessage
     */
    public function andMetaData(array $metadata = [])
    {
        if (empty($metadata)) {
            return $this;
        }

        return new GenericMessage(
            $this->getPayload(),
            $this->getMetaData()->mergeWith($metadata),
            $this->getIdentifier()
        );
    }

    /**
     * @param array $metadata
     * @return GenericMessage
     */
    public function withMetaData(array $metadata = [])
    {
        if ($this->getMetaData()->isEqualTo($metadata)) {
            return $this;
        }

        return new GenericMessage($this->getPayload(), new MetaData($metadata), $this->getIdentifier());
    }

}
