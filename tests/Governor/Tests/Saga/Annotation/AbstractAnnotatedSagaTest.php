<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
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
 * @author 255196
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

    public function associateWith(\Governor\Framework\Saga\AssociationValue $associationValue)
    {
        parent::associateWith($associationValue);
    }

    public function removeAssociationWith(\Governor\Framework\Saga\AssociationValue $associationValue)
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
