<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Saga;

use Governor\Framework\Saga\Annotation\AssociationValuesImpl;
use Governor\Framework\Domain\EventMessageInterface;
use Governor\Framework\Domain\GenericEventMessage;

/**
 * Description of AbstractSagaManagerTest
 *
 * @author david
 */
class AbstractSagaManagerTest extends \PHPUnit_Framework_TestCase
{

    private $testSubject;
    private $mockSagaRepository;
    private $mockSaga1;
    private $mockSaga2;
    private $mockSaga3;
    private $sagaCreationPolicy;
    private $associationValue;
    private $associationValues;
    private $mockSagaFactory;

    public function setUp()
    {
        $this->mockSagaRepository = $this->getMock('Governor\Framework\Saga\SagaRepositoryInterface');
        $this->mockSagaFactory = $this->getMock('Governor\Framework\Saga\SagaFactoryInterface');
        $this->mockSaga1 = $this->getMock('Governor\Framework\Saga\SagaInterface');
        $this->mockSaga2 = $this->getMock('Governor\Framework\Saga\SagaInterface');
        $this->mockSaga3 = $this->getMock('Governor\Framework\Saga\SagaInterface');
        $this->associationValue = new AssociationValue("association", "value");
        $this->associationValues = new AssociationValuesImpl();
        $this->associationValues->add($this->associationValue);
        $this->sagaCreationPolicy = SagaCreationPolicy::NONE;

        $this->mockSaga1->expects($this->any())
                ->method('isActive')
                ->will($this->returnValue(true));

        $this->mockSaga2->expects($this->any())
                ->method('isActive')
                ->will($this->returnValue(true));

        $this->mockSaga3->expects($this->any())
                ->method('isActive')
                ->will($this->returnValue(false));

        $this->mockSaga1->expects($this->any())
                ->method('getSagaIdentifier')
                ->will($this->returnValue("saga1"));

        $this->mockSaga2->expects($this->any())
                ->method('getSagaIdentifier')
                ->will($this->returnValue("saga2"));

        $this->mockSaga3->expects($this->any())
                ->method('getSagaIdentifier')
                ->will($this->returnValue("saga3"));

        $this->mockSagaRepository->expects($this->any())
                ->method('load')
                ->will($this->returnCallback(function () {
                            $args = func_get_args();

                            switch ($args[0]) {
                                case 'saga1':
                                    return $this->mockSaga1;
                                case 'saga2':
                                    return $this->mockSaga2;
                                case 'saga3':
                                    return $this->mockSaga3;
                            }
                        }));

        $this->mockSagaRepository->expects($this->any())
                ->method('find')
                ->will($this->returnValue(array("saga1", "saga2", "saga3")));

        $this->mockSaga1->expects($this->any())
                ->method('getAssociationValues')
                ->will($this->returnValue($this->associationValues));

        $this->mockSaga2->expects($this->any())
                ->method('getAssociationValues')
                ->will($this->returnValue($this->associationValues));

        $this->mockSaga3->expects($this->any())
                ->method('getAssociationValues')
                ->will($this->returnValue($this->associationValues));

        $this->testSubject = new TestableAbstractSagaManager($this->mockSagaRepository,
                $this->mockSagaFactory,
                array('Governor\Framework\Saga\SagaInterface'),
                $this->sagaCreationPolicy, $this->associationValue);
    }

    public function testSagasLoadedAndCommitted()
    {
        $event = new GenericEventMessage(new \stdClass());

        $this->mockSaga1->expects($this->once())
                ->method('handle')
                ->with($this->equalTo($event));

        $this->mockSaga2->expects($this->once())
                ->method('handle')
                ->with($this->equalTo($event));

        $this->mockSaga3->expects($this->never())
                ->method('handle');

        $this->mockSagaRepository->expects($this->exactly(2))
                ->method('commit');

        $this->testSubject->handle($event);
    }

    public function testExceptionPropagated()
    {
        $this->testSubject->setSuppressExceptions(false);
        $event = new GenericEventMessage(new \stdClass());

        $this->mockSaga1->expects($this->once())
                ->method('handle')
                ->with($this->equalTo($event))
                ->will($this->throwException(new \RuntimeException("Mock")));

        $this->mockSaga2->expects($this->never())
                ->method('handle');

        $this->mockSagaRepository->expects($this->once())
                ->method('commit')
                ->with($this->equalTo($this->mockSaga1));

        try {
            $this->testSubject->handle($event);
            $this->fail("Expected exception to be propagated");
        } catch (\RuntimeException $ex) {
            $this->assertEquals("Mock", $ex->getMessage());
        }
    }

    public function testExceptionSuppressed()
    {
        $event = new GenericEventMessage(new \stdClass());

        $this->mockSaga1->expects($this->once())
                ->method('handle')
                ->with($this->equalTo($event))
                ->will($this->throwException(new \RuntimeException("Mock")));

        $this->mockSaga1->expects($this->once())
                ->method('handle');

        $this->mockSaga2->expects($this->once())
                ->method('handle');

        $this->mockSagaRepository->expects($this->exactly(2))
                ->method('commit');

        $this->testSubject->handle($event);
    }

}

class TestableAbstractSagaManager extends AbstractSagaManager
{

    private $associationValue;
    private $sagaCreationPolicy;

    public function __construct(SagaRepositoryInterface $sagaRepository,
            SagaFactoryInterface $sagaFactory, array $sagaTypes,
            $sagaCreationPolicy, AssociationValue $associationValue)
    {
        parent::__construct($sagaRepository, $sagaFactory, $sagaTypes);
        $this->associationValue = $associationValue;
        $this->sagaCreationPolicy = $sagaCreationPolicy;
    }

    protected function extractAssociationValues($sagaType,
            EventMessageInterface $event)
    {
        return array($this->associationValue);
    }

    protected function getSagaCreationPolicy($sagaType,
            EventMessageInterface $event)
    {
        return new SagaInitializationPolicy($this->sagaCreationPolicy,
                $this->associationValue);
    }

}

/*
      @Override
      public Class<?> getTargetType() {
      return Saga.class;
      }

 */