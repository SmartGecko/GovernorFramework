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

/**
 * Default implementation of the @see EventMessageInterface
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
class GenericEventMessage extends GenericMessage implements EventMessageInterface
{

    /**
     * @var \DateTime
     */
    private $timestamp;

    /**
     * @param mixed $payload
     * @param MetaData $metadata
     * @param string $id
     * @param \DateTime $timestamp
     */
    public function __construct(
        $payload,
        MetaData $metadata = null,
        $id = null,
        \DateTime $timestamp = null
    ) {
        parent::__construct($payload, $metadata, $id);
        $this->timestamp = isset($timestamp) ? $timestamp : new \DateTime();
    }

    /**
     * @return \DateTime
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * @param mixed $event
     * @return GenericEventMessage
     */
    public static function asEventMessage($event)
    {
        if ($event instanceof EventMessageInterface) {
            return $event;
        } else {
            if ($event instanceof MessageInterface) {
                return new GenericEventMessage($event->getPayload(), $event->getMetaData(), $event->getIdentifier());
            }
        }

        return new GenericEventMessage($event);
    }

    /**
     * @param array $metadata
     * @return GenericEventMessage
     */
    public function andMetaData(array $metadata = [])
    {
        if (empty($metadata)) {
            return $this;
        }

        return new GenericEventMessage(
            $this->getPayload(),
            $this->getMetaData()->mergeWith($metadata),
            $this->getIdentifier(),
            $this->getTimestamp()
        );
    }

    /**
     * @param array $metadata
     * @return GenericEventMessage
     */
    public function withMetaData(array $metadata = [])
    {
        if ($this->getMetaData()->isEqualTo($metadata)) {
            return $this;
        }

        return new GenericEventMessage(
            $this->getPayload(),
            new MetaData($metadata),
            $this->getIdentifier(),
            $this->getTimestamp()
        );
    }


}
