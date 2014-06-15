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

use Governor\Framework\Saga\SagaInitializationPolicy;
use Governor\Framework\Saga\SagaCreationPolicy;
use Governor\Framework\Domain\EventMessageInterface;
use Governor\Framework\Saga\AbstractSagaManager;
use Governor\Framework\Saga\SagaInterface;

/**
 * Implementation of the SagaManager that uses annotations on the Sagas to describe the lifecycle management. Unlike
 * the SimpleSagaManager, this implementation can manage several types of Saga in a single AnnotatedSagaManager.
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a> 
 */
class AnnotatedSagaManager extends AbstractSagaManager
{

    private $parameterResolverFactory;

    protected function getSagaCreationPolicy($sagaType,
            EventMessageInterface $event)
    {
        $inspector = new SagaMethodMessageHandlerInspector($sagaType);
        $handlers = $inspector->getMessageHandlers($event);

        foreach ($handlers as $handler) {
            if ($handler->getCreationPolicy() !== SagaCreationPolicy::NONE) {
                return new SagaInitializationPolicy($handler->getCreationPolicy(),
                        $handler->getAssociationValue($event));
            }
        }

        return new SagaInitializationPolicy(SagaCreationPolicy::NONE, null);
    }

    protected function extractAssociationValues($sagaType,
            EventMessageInterface $event)
    {
        $inspector = new SagaMethodMessageHandlerInspector($sagaType);
        $handlers = $inspector->getMessageHandlers($event);
        $values = array();

        foreach ($handlers as $handler) {
            $values[] = $handler->getAssociationValue($event);
        }

        return $values;
    }

    protected function preProcessSaga(SagaInterface $saga)
    {
        if (null !== $this->parameterResolverFactory) {
            $saga->registerParameterResolverFactory($this->parameterResolverFactory);
        }
    }

    public function getTargetType()
    {
        return current($this->getManagedSagaTypes());        
    }

}
