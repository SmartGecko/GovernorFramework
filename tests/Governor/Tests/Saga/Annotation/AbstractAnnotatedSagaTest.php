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

namespace Governor\Tests\Saga\Annotation;

use Governor\Framework\Annotations\SagaEventHandler;
use Governor\Framework\Annotations\EndSaga;
use Governor\Framework\Domain\GenericEventMessage;
use Governor\Framework\Saga\AssociationValue;
use Governor\Framework\Saga\Annotation\AbstractAnnotatedSaga;

/**
 * Description of AbstractAnnotatedSagaTest
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
class AbstractAnnotatedSagaTest extends \PHPUnit_Framework_TestCase
{

    public function testInvokeSaga()
    {
        $testSubject = new StubAnnotatedSaga();
        $testSubject->associateWith(new AssociationValue("propertyName", "id"));
        $testSubject->handle(new GenericEventMessage(new RegularEvent("id")));
        $testSubject->handle(new GenericEventMessage(new RegularEvent("wrongId")));
        $testSubject->handle(new GenericEventMessage(new \stdClass()));
        $this->assertEquals(1, $testSubject->invocationCount);
    }

    public function testSerializeAndInvokeSaga()
    {        
        $original = new StubAnnotatedSaga();
        $original->associateWith(new AssociationValue("propertyName", "id"));
        $serialized = serialize($original);     
        $testSubject = unserialize($serialized);
        $testSubject->handle(new GenericEventMessage(new RegularEvent("id")));
        $testSubject->handle(new GenericEventMessage(new \stdClass()));
        $this->assertEquals(1, $testSubject->invocationCount);
    }

    public function testEndedAfterInvocation_BeanProperty()
    {
        $testSubject = new StubAnnotatedSaga();
        $testSubject->associateWith(new AssociationValue("propertyName", "id"));
        $testSubject->handle(new GenericEventMessage(new RegularEvent("id")));
        $testSubject->handle(new GenericEventMessage(new \stdClass()));
        $testSubject->handle(new GenericEventMessage(new SagaEndEvent("id")));
        $this->assertEquals(2, $testSubject->invocationCount);
        $this->assertFalse($testSubject->isActive());
    }

    public function testEndedAfterInvocation_WhenAssociationIsRemoved()
    {
        $testSubject = new StubAnnotatedSagaWithExplicitAssociationRemoval();
        $testSubject->associateWith(new AssociationValue("propertyName", "id"));
        $testSubject->handle(new GenericEventMessage(new RegularEvent("id")));
        $testSubject->handle(new GenericEventMessage(new \stdClass()));
        $testSubject->handle(new GenericEventMessage(new SagaEndEvent("id")));
        $this->assertEquals(2, $testSubject->invocationCount);
        $this->assertFalse($testSubject->isActive());
    }

    public function testEndedAfterInvocation_UniformAccessPrinciple()
    {
        $testSubject = new StubAnnotatedSaga();
        $testSubject->associateWith(new AssociationValue("propertyName", "id"));
        $testSubject->handle(new GenericEventMessage(new UniformAccessEvent("id")));
        $testSubject->handle(new GenericEventMessage(new \stdClass()));
        $testSubject->handle(new GenericEventMessage(new SagaEndEvent("id")));
        $this->assertEquals(2, $testSubject->invocationCount);
        $this->assertFalse($testSubject->isActive());
    }

}

class StubAnnotatedSaga extends AbstractAnnotatedSaga
{

    public $invocationCount = 0;

    /**
     * @SagaEventHandler(associationProperty = "propertyName")
     */
    public function onRegularEvent(RegularEvent $event)
    {
        $this->invocationCount++;
    }

    /**
     * @SagaEventHandler(associationProperty = "propertyName")
     */
    public function onUniformAccessEvent(UniformAccessEvent $event)
    {
        $this->invocationCount++;
    }

    /**
     * @EndSaga
     * @SagaEventHandler(associationProperty = "propertyName")
     */
    public function onSagaEndEvent(SagaEndEvent $event)
    {
        $this->invocationCount++;
    }

    public function associateWith(AssociationValue $associationValue)
    {
        parent::associateWith($associationValue);
    }

    public function removeAssociationWith(AssociationValue $associationValue)
    {
        parent::removeAssociationWith($associationValue);
    }

}

class StubAnnotatedSagaWithExplicitAssociationRemoval extends StubAnnotatedSaga
{

    /**
     * @EndSaga
     * @SagaEventHandler(associationProperty = "propertyName")
     */
    public function onSagaEndEvent(SagaEndEvent $event)
    {
        // !!! TODO since this method overrides a handler, it doesn't need the annotations anymore
        parent::onSagaEndEvent($event);
        $this->removeAssociationWith(new AssociationValue("propertyName",
            $event->getPropertyName()));
    }

}

class RegularEvent
{

    private $propertyName;

    public function __construct($propertyName)
    {
        $this->propertyName = $propertyName;
    }

    public function getPropertyName()
    {
        return $this->propertyName;
    }

}

class UniformAccessEvent
{

    private $propertyName;

    public function __construct($propertyName)
    {
        $this->propertyName = $propertyName;
    }

    public function getPropertyName()
    {
        return $this->propertyName;
    }

}

class SagaEndEvent extends RegularEvent
{

    public function __construct($propertyName)
    {
        parent::__construct($propertyName);
    }

}
