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

use Governor\Framework\Annotations\TargetAggregateIdentifier;
use Governor\Framework\Annotations\TargetAggregateVersion;
use Governor\Framework\Common\ReflectionUtils;
use Doctrine\Common\Annotations\AnnotationReader;

/**
 * Implementation of the {@see CommandTargetResolverInterface} that uses annotations to resolve its target.
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a> 
 */
class AnnotationCommandTargetResolver implements CommandTargetResolverInterface
{

    /**
     * 
     * @param CommandMessageInterface $command
     * @return VersionedAggregateIdentifier
     * @throws \InvalidArgumentException
     */
    public function resolveTarget(CommandMessageInterface $command)
    {
        $reader = new AnnotationReader();
        $reflClass = new \ReflectionClass($command->getPayload());

        $id = $this->findIdentifier($command, $reader, $reflClass);
        $version = $this->findVersion($command, $reader, $reflClass);

        if (null === $id) {
            throw new \InvalidArgumentException(
            sprintf("Invalid command. It does not identify the target aggregate. " .
                    "Make sure at least one of the fields or methods in the [%s] class contains the " .
                    "@TargetAggregateIdentifier annotation and that it returns a non-null value.",
                    $command->getPayloadType()));
        }

        return new VersionedAggregateIdentifier($id, $version);
    }

    private function getAnnotatedTargetValue($annotationName,
            CommandMessageInterface $command, AnnotationReader $reader,
            \ReflectionClass $reflClass)
    {
        foreach (ReflectionUtils::getProperties($reflClass) as $property) {
            if (null !== $annot = $reader->getPropertyAnnotation($property,
                    $annotationName)) {
                $property->setAccessible(true);

                return $property->getValue($command->getPayload());
            }
        }

        foreach (ReflectionUtils::getMethods($reflClass) as $method) {
            if (null !== $annot = $reader->getMethodAnnotation($method,
                    $annotationName)) {
                $method->setAccessible(true);

                return $method->invoke($command->getPayload());
            }
        }

        return null;
    }

    private function findIdentifier(CommandMessageInterface $command,
            AnnotationReader $reader, \ReflectionClass $reflClass)
    {
        return $this->getAnnotatedTargetValue(TargetAggregateIdentifier::class,
                        $command, $reader, $reflClass);
    }

    private function findVersion(CommandMessageInterface $command,
            AnnotationReader $reader, \ReflectionClass $reflClass)
    {
        return $this->getAnnotatedTargetValue(TargetAggregateVersion::class,
                        $command, $reader, $reflClass);
    }

}
