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

namespace Governor\Tests\Saga;

use Governor\Framework\Saga\SagaInterface;
use Governor\Framework\Saga\Annotation\AssociationValuesImpl;
use Governor\Framework\Domain\EventMessageInterface;
use Governor\Framework\Domain\GenericEventMessage;
use Governor\Framework\Saga\AbstractSagaManager;
use Governor\Framework\Saga\SagaRepositoryInterface;
use Governor\Framework\Saga\AssociationValue;
use Governor\Framework\Saga\SagaCreationPolicy;
use Governor\Framework\Saga\SagaFactoryInterface;
use Governor\Framework\Saga\SagaInitializationPolicy;

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
        $this->mockSagaRepository = $this->getMock(SagaRepositoryInterface::class);
        $this->mockSagaFactory = $this->getMock(SagaFactoryInterface::class);
        $this->mockSaga1 = $this->getMock(SagaInterface::class);
        $this->mockSaga2 = $this->getMock(SagaInterface::class);
        $this->mockSaga3 = $this->getMock(SagaInterface::class);
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
        
        $this->testSubject->setLogger($this->getMock(\Psr\Log\LoggerInterface::class));
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

    public function getTargetType()
    {
        return SagaInterface::class;
    }

}