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

namespace Governor\Framework\Saga\Annotation;

use Doctrine\Common\Comparable;
use Governor\Framework\Annotations\StartSaga;
use Doctrine\Common\Annotations\AnnotationReader;
use Governor\Framework\Saga\AssociationValue;
use Governor\Framework\Domain\EventMessageInterface;
use Governor\Framework\Saga\SagaCreationPolicy;
use Governor\Framework\Annotations\SagaEventHandler;
use Governor\Framework\Annotations\EndSaga;
use Governor\Framework\Common\Property\PropertyAccessStrategy;

/**
 * Description of SagaMethodMessageHandler
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
class SagaMethodMessageHandler implements Comparable
{

    /**
     * @var AnnotationReader
     */
    private $reader;

    /**
     * @var integer
     */
    private $creationPolicy;
    private $handlerMethod;
    private $associationKey;

    /**
     *
     * @var  \Governor\Framework\Common\Property\PropertyInterface
     */
    private $associationProperty;

    public static function noHandlers()
    {
        return new SagaMethodMessageHandler(
            SagaCreationPolicy::NONE, null,
            null, null, null
        );
    }

    public static function getInstance(
        EventMessageInterface $event,
        \ReflectionMethod $handlerMethod
    ) {
        $reader = new AnnotationReader();
        $handlerAnnotation = $reader->getMethodAnnotation(
            $handlerMethod,
            SagaEventHandler::class
        );

        $associationProperty = PropertyAccessStrategy::getProperty(
            $event->getPayload(),
            $handlerAnnotation->associationProperty
        );

        if (null === $associationProperty) {
            throw new \RuntimeException(
                sprintf(
                    "SagaEventHandler %s::%s defines a property %s that is not ".
                    "defined on the Event it declares to handle (%s)",
                    $handlerMethod->class,
                    $handlerMethod->name,
                    $handlerAnnotation->associationProperty,
                    $event->getPayloadType()
                )
            );
        }

        $associationKey = (empty($handlerAnnotation->keyName)) ? $handlerAnnotation->associationProperty
            : $handlerAnnotation->keyName;
        $startAnnotation = $reader->getMethodAnnotation($handlerMethod, StartSaga::class);

        if (null === $startAnnotation) {
            $sagaCreationPolicy = SagaCreationPolicy::NONE;
        } else {
            if ($startAnnotation->forceNew) {
                $sagaCreationPolicy = SagaCreationPolicy::ALWAYS;
            } else {
                $sagaCreationPolicy = SagaCreationPolicy::IF_NONE_FOUND;
            }
        }

        return new SagaMethodMessageHandler(
            $sagaCreationPolicy,
            $associationKey, $associationProperty, $handlerMethod, $reader
        );
    }

    private function __construct(
        $creationPolicy,
        $associationKey,
        $associationProperty,
        \ReflectionMethod $method = null,
        AnnotationReader $reader = null
    ) {
        $this->reader = $reader;
        $this->creationPolicy = $creationPolicy;
        $this->handlerMethod = $method;
        $this->associationKey = $associationKey;
        $this->associationProperty = $associationProperty;
    }

    public function getCreationPolicy()
    {
        return $this->creationPolicy;
    }

    /**
     * Indicates whether the inspected method is an Event Handler.
     *
     * @return boolean true if the saga has a handler
     */
    public function isHandlerAvailable()
    {
        return null !== $this->handlerMethod;
    }

    public function isEndingHandler()
    {
        return $this->isHandlerAvailable() &&
        null !== $this->reader->getMethodAnnotation(
            $this->handlerMethod,
            EndSaga::class
        );
    }

    public function invoke($target, EventMessageInterface $event)
    {
        if (!$this->isHandlerAvailable()) {
            return;
        }

        $this->handlerMethod->setAccessible(true);
        $this->handlerMethod->invokeArgs($target, array($event->getPayload()));
    }

    /**
     * The AssociationValue to find the saga instance with, or <code>null</code> if no AssociationValue can be found on
     * the given <code>eventMessage</code>.
     *
     * @param EventMessageInterface $eventMessage The event message containing the value of the association
     * @return AssociationValue The AssociationValue to find the saga instance with, or <code>null</code> if none found
     */
    public function getAssociationValue(EventMessageInterface $eventMessage)
    {
        if (null === $this->associationProperty) {
            return null;
        }

        $associationValue = $this->associationProperty->getValue($eventMessage->getPayload());

        return (null === $associationValue) ? null : new AssociationValue(
            $this->associationKey,
            $associationValue
        );
    }

    public function compareTo($other)
    {

    }

}
