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

namespace Governor\Framework\EventSourcing;

use Governor\Framework\Common\ReflectionUtils;
use Governor\Framework\Domain\DomainEventMessageInterface;
use Governor\Framework\EventSourcing\EventSourcedAggregateRootInterface;

/**
 * Description of GenericAggregateFactory
 *
 * @author david
 */
class GenericAggregateFactory extends AbstractAggregateFactory
{

    /**
     * @var string
     */
    private $aggregateType;

    /**
     * @var string
     */
    private $typeIdentifier;

    /**
     * @var \ReflectionClass
     */
    private $reflectionClass;

    /**
     * @param string $aggregateType
     */
    function __construct($aggregateType)
    {
        if (null === $aggregateType) {
            throw new \InvalidArgumentException("Aggregate type not set.");
        }

        $this->reflectionClass = new \ReflectionClass($aggregateType);

        if (!$this->reflectionClass->implementsInterface(EventSourcedAggregateRootInterface::class)) {
            throw new \InvalidArgumentException(
                "The given aggregateType must be a subtype of EventSourcedAggregateRootInterface"
            );
        }

        $this->aggregateType = $aggregateType;
        $this->typeIdentifier = $this->reflectionClass->getName();
    }

    /**
     * {@inheritdoc}
     */
    protected function doCreateAggregate(
        $aggregateIdentifier,
        DomainEventMessageInterface $firstEvent
    ) {
        $aggregate = $this->reflectionClass->newInstanceWithoutConstructor();

        return $aggregate;
    }

    /**
     * {@inheritdoc}
     */
    public function getAggregateType()
    {
        return $this->aggregateType;
    }

    /**
     * {@inheritdoc}
     */
    public function getTypeIdentifier()
    {
        return $this->typeIdentifier;
    }

}
