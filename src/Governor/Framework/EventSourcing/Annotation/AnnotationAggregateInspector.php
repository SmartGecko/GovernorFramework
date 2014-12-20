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

namespace Governor\Framework\EventSourcing\Annotation;

use Doctrine\Common\Annotations\AnnotationReader;
use Governor\Framework\Annotations\AggregateIdentifier;
use Governor\Framework\Annotations\EventHandler;
use Governor\Framework\Annotations\EventSourcedMember;
use Governor\Framework\Common\ReflectionUtils;
use Governor\Framework\Domain\DomainEventMessageInterface;
use Governor\Framework\EventSourcing\IncompatibleAggregateException;

/**
 * Description of AnnotationAggregateInspector
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
class AnnotationAggregateInspector
{

    /**
     * @var mixed
     */
    private $targetObject;

    /**
     * @var \ReflectionClass
     */
    private $reflectionClass;

    /**
     * @var AnnotationReader
     */
    private $reader;

    /**
     * @param $targetObject
     */
    public function __construct($targetObject)
    {
        $this->targetObject = $targetObject;
        $this->reader = new AnnotationReader();
        $this->reflectionClass = ReflectionUtils::getClass($this->targetObject);
    }

    /**
     * @return string
     * @throws IncompatibleAggregateException
     */
    public function getIdentifier()
    {
        foreach (ReflectionUtils::getProperties($this->reflectionClass) as $property) {
            $annotation = $this->reader->getPropertyAnnotation(
                $property,
                AggregateIdentifier::class
            );

            if (null !== $annotation) {
                $property->setAccessible(true);

                return $property->getValue($this->targetObject);
            }
        }

        throw new IncompatibleAggregateException(
            sprintf(
                "The aggregate class [%s] does not specify an Identifier. " .
                "Ensure that the field containing the aggregate " .
                "identifier is annotated with @AggregateIdentifier.",
                $this->reflectionClass->getName()
            )
        );
    }


    /**
     * @return array
     */
    public function getChildEntities()
    {
        $entities = array();

        foreach (ReflectionUtils::getProperties($this->reflectionClass) as $property) {
            $annotation = $this->reader->getPropertyAnnotation(
                $property,
                EventSourcedMember::class
            );

            if (null !== $annotation) {
                $property->setAccessible(true);
                $child = $property->getValue($this->targetObject);


                if (is_array($child)) {
                    $entities = array_merge($entities, $child);
                } else {
                    if ($child instanceof \IteratorAggregate) {
                        foreach ($child as $element) {
                            $entities[] = $element;
                        }
                    } else {
                        $entities[] = $child;
                    }
                }
            }
        }

        return $entities;
    }

    /**
     * @param DomainEventMessageInterface $event
     */
    public function findAndInvokeEventHandlers(DomainEventMessageInterface $event)
    {
        // !!! TODO revisit
        foreach (ReflectionUtils::getMethods($this->reflectionClass) as $method) {
            $annotation = $this->reader->getMethodAnnotation(
                $method,
                EventHandler::class
            );

            if (null !== $annotation) {
                $parameter = current($method->getParameters());

                if (null !== $parameter->getClass() && $parameter->getClass()->name
                    === $event->getPayloadType()
                ) {
                    $method->invokeArgs(
                        $this->targetObject,
                        array($event->getPayload())
                    );
                }
            }
        }
    }

}
