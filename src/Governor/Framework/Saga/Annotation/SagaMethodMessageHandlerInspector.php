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

use Doctrine\Common\Annotations\AnnotationReader;
use Governor\Framework\Domain\EventMessageInterface;
use Governor\Framework\Common\ReflectionUtils;
use Governor\Framework\Annotations\SagaEventHandler;

/**
 * The SagaMethodMessageHandlerInspector is using annotations to find the correct message handlers of a Saga.
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
class SagaMethodMessageHandlerInspector
{

    private $targetSaga;
    private $reader;

    public function __construct($targetSaga)
    {
        $this->targetSaga = $targetSaga;
        $this->reader = new AnnotationReader();
    }

    // !!! TODO use the inspector for this
    public function getMessageHandlers(EventMessageInterface $event)
    {
        $found = array();
        $reflectionClass = ReflectionUtils::getClass($this->targetSaga);

        foreach (ReflectionUtils::getMethods($reflectionClass) as $method) {
            $annot = $this->reader->getMethodAnnotation(
                $method,
                SagaEventHandler::class
            );

            if (null === $annot) {
                continue;
            }

            if (0 === count($method->getParameters())) {
                throw new \RuntimeException(
                    sprintf(
                        "Invalid method signature detected of %s::%s. ".
                        "Methods annotated with @SagaEventHandler must have exatly one parameter with the type of the message they handle. ",
                        $reflectionClass->name,
                        $method->name
                    )
                );
            }

            $parameter = current($method->getParameters());

            if (null !== $parameter->getClass() &&
                $parameter->getClass()->name === $event->getPayloadType()
            ) {
                $found[] = SagaMethodMessageHandler::getInstance(
                    $event,
                    $method,
                    $annot
                );
            }
        }

        return $found;
    }

    public function findHandlerMethod(
        AbstractAnnotatedSaga $target,
        EventMessageInterface $event
    ) {
        foreach ($this->getMessageHandlers($event) as $handler) {
            $associationValue = $handler->getAssociationValue($event);
            if ($target->getAssociationValues()->contains($associationValue)) {
                return $handler;
            }
        }

        return SagaMethodMessageHandler::noHandlers();
        /*   for (SagaMethodMessageHandler handler : getMessageHandlers(event)) {
          final AssociationValue associationValue = handler.getAssociationValue(event);
          if (target.getAssociationValues().contains(associationValue)) {
          return handler;
          } else if (logger.isDebugEnabled()) {
          logger.debug(
          "Skipping handler [{}], it requires an association value [{}:{}] that this Saga is not associated with",
          handler.getName(),
          associationValue.getKey(),
          associationValue.getValue());
          }
          }
          if (logger.isDebugEnabled()) {
          logger.debug("No suitable handler was found for event of type", event.getPayloadType().getName());
          }
          return SagaMethodMessageHandler.noHandler(); */
    }

    public function getSagaType()
    {
        return $this->sagaType;
    }

}
