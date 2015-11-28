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

use Ramsey\Uuid\Uuid;
use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\PostDeserialize;
use Governor\Framework\Domain\EventMessageInterface;
use Governor\Framework\Saga\SagaInterface;
use Governor\Framework\Saga\AssociationValue;
use Governor\Framework\Saga\Annotation\AssociationValuesImpl;
use Governor\Framework\Saga\AssociationValuesInterface;

/**
 * Implementation of the {@link Saga interface} that delegates incoming events to SagaEventHandler annotated methods.
 * 
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a> 
 */
abstract class AbstractAnnotatedSaga implements SagaInterface
{

    /**
     * @Type ("Governor\Framework\Saga\Annotation\AssociationValuesImpl")
     * @var AssociationValuesInterface 
     */
    private $associationValues;

    /**
     * @Type ("string")
     * @var string 
     */
    private $identifier;

    /**
     * @Type ("boolean")
     * @var boolean 
     */
    private $isActive = true;

    /**
     * @Exclude
     * @var SagaMethodMessageHandlerInspector
     */
    private $inspector;

    /**
     * Initialize the saga. If an identifier is provided it will be used, otherwise a random UUID will be generated.
     * 
     * @param string $identifier
     */
    public function __construct($identifier = null)
    {
        $this->identifier = (null === $identifier) ? Uuid::uuid1()->toString() : $identifier;
        $this->associationValues = new AssociationValuesImpl();
        $this->inspector = new SagaMethodMessageHandlerInspector($this);
        $this->associationValues->add(new AssociationValue('sagaIdentifier',
                $this->identifier));
    }

    /**
     * @PostDeserialize
     */
    public function postDeserialize()
    {
        $this->inspector = new SagaMethodMessageHandlerInspector($this);
    }

    /**
     * {@inheritdoc}
     */
    public function getSagaIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @return AssociationValuesInterface
     */
    public function getAssociationValues()
    {
        return $this->associationValues;
    }

    public final function handle(EventMessageInterface $event)
    {        
        if ($this->isActive()) {
            // find and invoke handler
            $handler = $this->inspector->findHandlerMethod($this, $event);
            $handler->invoke($this, $event);

            if ($handler->isEndingHandler()) {
                $this->end();
            }
        }
    }

    /**
     * @return boolean
     */
    public function isActive()
    {
        return $this->isActive;
    }

    /**
     * Marks the saga as ended. Ended saga's may be cleaned up by the repository when they are committed.
     */
    protected function end()
    {
        $this->isActive = false;
    }

    /**
     * Registers a AssociationValue with the given saga. When the saga is committed, it can be found using the
     * registered property.
     *
     * @param AssociationValue $associationValue The value to associate this saga with.
     */
    public function associateWith(AssociationValue $associationValue)
    {                
        $this->associationValues->add($associationValue);
    }

    /**
     * Removes the given association from this Saga. When the saga is committed, it can no longer be found using the
     * given association. If the given property wasn't registered with the saga, nothing happens.
     *
     * @param AssociationValue $associationValue the association value to remove from the saga.
     */
    public function removeAssociationWith(AssociationValue $associationValue)
    {
        $this->associationValues->remove($associationValue);    
    }

}
