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
 * Description of GenericDomainEventMessage
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
class GenericDomainEventMessage extends GenericEventMessage implements DomainEventMessageInterface
{

    /**
     * @var string
     */
    private $aggregateIdentifier;
    /**
     * @var int
     */
    private $scn;

    /**
     *
     * @param string $aggregateIdentifier
     * @param integer $scn
     * @param mixed $payload
     * @param MetaData $metadata
     * @param string $id
     * @param \DateTime $timestamp
     */
    public function __construct(
        $aggregateIdentifier,
        $scn,
        $payload,
        MetaData $metadata = null,
        $id = null,
        \DateTime $timestamp = null
    ) {
        parent::__construct($payload, $metadata, $id, $timestamp);
        $this->aggregateIdentifier = $aggregateIdentifier;
        $this->scn = $scn;
    }

    /**
     * @return string
     */
    public function getAggregateIdentifier()
    {
        return $this->aggregateIdentifier;
    }

    /**
     * @return int
     */
    public function getScn()
    {
        return $this->scn;
    }

    /**
     *
     * @param array $metadata
     * @return GenericDomainEventMessage
     */
    public function andMetaData(array $metadata = [])
    {
        if (empty($metadata)) {
            return $this;
        }

        return new GenericDomainEventMessage(
            $this->getAggregateIdentifier(),
            $this->scn,
            $this->getPayload(),
            $this->getMetaData()->mergeWith($metadata),
            $this->getIdentifier(),
            $this->getTimestamp()
        );
    }

    /**
     *
     * @param array $metadata
     * @return GenericDomainEventMessage
     */
    public function withMetaData(array $metadata = [])
    {
        if ($this->getMetaData()->isEqualTo($metadata)) {
            return $this;
        }

        return new GenericDomainEventMessage(
            $this->aggregateIdentifier,
            $this->scn,
            $this->getPayload(),
            new MetaData($metadata),
            $this->getIdentifier(),
            $this->getTimestamp()
        );
    }

}
