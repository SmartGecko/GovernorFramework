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

use Governor\Framework\Common\Annotation\AnnotationReaderFactoryInterface;
use Governor\Framework\Common\ReflectionUtils;
use Governor\Framework\Annotations\TargetAggregateIdentifier;
use Governor\Framework\CommandHandling\CommandMessageInterface;

class AnnotationRoutingStrategy extends AbstractRoutingStrategy
{
    /**
     * @var \Doctrine\Common\Annotations\Reader
     */
    private $reader;

    /**
     * @param AnnotationReaderFactoryInterface $annotationReaderFactory
     * @param int $unresolvedRoutingKeyPolicy
     */
    public function __construct(
        AnnotationReaderFactoryInterface $annotationReaderFactory,
        $unresolvedRoutingKeyPolicy = UnresolvedRoutingKeyPolicy::ERROR
    ) {
        parent::__construct($unresolvedRoutingKeyPolicy);
        $this->reader = $annotationReaderFactory->getReader();
    }

    /**
     * @param $annotationName
     * @param CommandMessageInterface $command
     * @param \ReflectionClass $reflectionClass
     * @return mixed|null
     */
    private function getAnnotatedTargetValue(
        $annotationName,
        CommandMessageInterface $command,
        \ReflectionClass $reflectionClass
    ) {
        foreach (ReflectionUtils::getProperties($reflectionClass) as $property) {
            if (null !== $annotation = $this->reader->getPropertyAnnotation(
                    $property,
                    $annotationName
                )
            ) {
                $property->setAccessible(true);

                return $property->getValue($command->getPayload());
            }
        }

        foreach (ReflectionUtils::getMethods($reflectionClass) as $method) {
            if (null !== $annotation = $this->reader->getMethodAnnotation(
                    $method,
                    $annotationName
                )
            ) {
                $method->setAccessible(true);

                return $method->invoke($command->getPayload());
            }
        }

        return null;
    }

    /**
     * @param CommandMessageInterface $command
     * @param \ReflectionClass $reflectionClass
     * @return mixed|null
     */
    private function findIdentifier(
        CommandMessageInterface $command,
        \ReflectionClass $reflectionClass
    ) {
        return $this->getAnnotatedTargetValue(
            TargetAggregateIdentifier::class,
            $command,
            $reflectionClass
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function doResolveRoutingKey(CommandMessageInterface $command)
    {
        $reflectionClass = new \ReflectionClass($command->getPayload());

        return $this->findIdentifier($command, $reflectionClass);
    }

}