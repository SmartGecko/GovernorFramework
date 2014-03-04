<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Saga\Annotation;

use Governor\Framework\Domain\GenericEventMessage;
use Governor\Framework\Saga\AssociationValue;

/**
 * Description of AbstractAnnotatedSagaTest
 *
 * @author 255196
 */
class AbstractAnnotatedSagaTest extends \PHPUnit_Framework_TestCase
{

    public function testInvokeSaga()
    {
      /*  $testSubject = new StubAnnotatedSaga();
        $testSubject->associateWith(new AssociationValue("propertyName", "id"));
        $testSubject->handle(new GenericEventMessage(new RegularEvent("id")));
        $testSubject->handle(new GenericEventMessage(new RegularEvent("wrongId")));
        $testSubject->handle(new GenericEventMessage(new \stdClass()));
        $this->assertEquals(1, $testSubject->invocationCount);*/
    }

    /*
      @Test
      public void testSerializeAndInvokeSaga() throws Exception {
      ByteArrayOutputStream baos = new ByteArrayOutputStream();
      final StubAnnotatedSaga original = new StubAnnotatedSaga();
      original.associateWith("propertyName", "id");
      new ObjectOutputStream(baos).writeObject(original);
      StubAnnotatedSaga testSubject = (StubAnnotatedSaga) new ObjectInputStream(new ByteArrayInputStream(baos.toByteArray()))
      .readObject();
      testSubject.handle(new GenericEventMessage<RegularEvent>(new RegularEvent("id")));
      testSubject.handle(new GenericEventMessage<Object>(new Object()));
      assertEquals(1, testSubject.invocationCount);
      }

      @Test
      public void testEndedAfterInvocation_BeanProperty() {
      StubAnnotatedSaga testSubject = new StubAnnotatedSaga();
      testSubject.associateWith("propertyName", "id");
      testSubject.handle(new GenericEventMessage<RegularEvent>(new RegularEvent("id")));
      testSubject.handle(new GenericEventMessage<Object>(new Object()));
      testSubject.handle(new GenericEventMessage<SagaEndEvent>(new SagaEndEvent("id")));
      assertEquals(2, testSubject.invocationCount);
      assertFalse(testSubject.isActive());
      }

      @Test
      public void testEndedAfterInvocation_WhenAssociationIsRemoved() {
      StubAnnotatedSaga testSubject = new StubAnnotatedSagaWithExplicitAssociationRemoval();
      testSubject.associateWith("propertyName", "id");
      testSubject.handle(new GenericEventMessage<RegularEvent>(new RegularEvent("id")));
      testSubject.handle(new GenericEventMessage<Object>(new Object()));
      testSubject.handle(new GenericEventMessage<SagaEndEvent>(new SagaEndEvent("id")));
      assertEquals(2, testSubject.invocationCount);
      assertFalse(testSubject.isActive());
      }

      @Test
      public void testEndedAfterInvocation_UniformAccessPrinciple() {
      StubAnnotatedSaga testSubject = new StubAnnotatedSaga();
      testSubject.associateWith("propertyName", "id");
      testSubject.handle(new GenericEventMessage<UniformAccessEvent>(new UniformAccessEvent("id")));
      testSubject.handle(new GenericEventMessage<Object>(new Object()));
      testSubject.handle(new GenericEventMessage<SagaEndEvent>(new SagaEndEvent("id")));
      assertEquals(2, testSubject.invocationCount);
      assertFalse(testSubject.isActive());
      } */
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

    public function onSagaEndEvent(SagaEndEvent $event)
    {
        // since this method overrides a handler, it doesn't need the annotations anymore
        parent::onSagaEndEvent($event);
        $this->removeAssociationWith("propertyName", $event->getPropertyName());
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
