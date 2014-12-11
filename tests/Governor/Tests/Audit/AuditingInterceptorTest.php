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

namespace Governor\Tests\Audit;

use Governor\Framework\Audit\AuditDataProviderInterface;
use Governor\Framework\Audit\AuditLoggerInterface;
use Governor\Framework\EventHandling\EventBusInterface;
use Governor\Framework\CommandHandling\GenericCommandMessage;
use Governor\Framework\CommandHandling\InterceptorChainInterface;
use Governor\Framework\UnitOfWork\CurrentUnitOfWork;
use Governor\Framework\UnitOfWork\DefaultUnitOfWork;
use Governor\Framework\UnitOfWork\SaveAggregateCallbackInterface;
use Governor\Tests\Stubs\StubAggregate;
use Governor\Framework\Audit\AuditingInterceptor;

/**
 * Description of AuditingInterceptorTest
 *
 * @author david
 */
class AuditingInterceptorTest extends \PHPUnit_Framework_TestCase
{

    private $testSubject;
    private $mockAuditDataProvider;
    private $mockAuditLogger;
    private $mockInterceptorChain;

    public function setUp()
    {
        $this->mockAuditDataProvider = $this->getMock(AuditDataProviderInterface::class);
        $this->mockAuditLogger = $this->getMock(AuditLoggerInterface::class);

        $this->testSubject = new AuditingInterceptor();
        $this->testSubject->setAuditDataProvider($this->mockAuditDataProvider);
        $this->testSubject->setAuditLogger($this->mockAuditLogger);

        $this->mockInterceptorChain = $this->getMock(InterceptorChainInterface::class);

        $this->mockAuditDataProvider->expects($this->any())
                ->method('provideAuditDataFor')
                ->will($this->returnValue(array('key' => 'value')));
    }

    public function tearDown()
    {
        while (CurrentUnitOfWork::isStarted()) {
            CurrentUnitOfWork::get()->rollback(null);
        }
    }

    public function testInterceptCommand_SuccessfulExecution()
    {
        $this->mockInterceptorChain->expects($this->any())
                ->method('proceed')
                ->will($this->returnValue("Return value"));

        $payload = new \stdClass();
        $payload->value = "Command";

        $mockEventBus = $this->getMock(EventBusInterface::class);
        $mockCallback = $this->getMock(SaveAggregateCallbackInterface::class);

        $uow = DefaultUnitOfWork::startAndGet();
        $aggregate = new StubAggregate();

        $uow->registerAggregate($aggregate, $mockEventBus, $mockCallback);

        $command = new GenericCommandMessage($payload);
        $result = $this->testSubject->handle($command, $uow,
                $this->mockInterceptorChain);

        $this->mockAuditDataProvider->expects($this->atLeast(1))
                ->method('provideAuditDataFor');

        $this->mockAuditLogger->expects($this->exactly(1))
                ->method('logSuccessful');

        $aggregate->doSomething();
        $aggregate->doSomething();

        $this->assertEquals("Return value", $result);
        $uow->commit();

        $eventFromAggregate = $aggregate->getUncommittedEvents()->next();
        $this->assertEquals("value",
                $eventFromAggregate->getMetaData()->get('key'));
    }

    public function testInterceptCommand_FailedExecution()
    {
        $exception = new \RuntimeException();
        $this->mockInterceptorChain->expects($this->any())
                ->method('proceed')
                ->will($this->throwException($exception));

        $uow = DefaultUnitOfWork::startAndGet();

        $payload = new \stdClass();
        $payload->value = "Command";

        $command = new GenericCommandMessage($payload);

        try {
            $this->testSubject->handle($command, $uow,
                    $this->mockInterceptorChain);
        } catch (\Exception $ex) {
            $this->assertSame($exception, $ex);
        }

        $aggregate = new StubAggregate();

        $mockEventBus = $this->getMock(EventBusInterface::class);
        $mockCallback = $this->getMock(SaveAggregateCallbackInterface::class);

        $this->mockAuditDataProvider->expects($this->exactly(2))
                ->method('provideAuditDataFor');

        $this->mockAuditLogger->expects($this->never())
                ->method('logSuccessful');

        $this->mockAuditLogger->expects($this->any())
                ->method('logFailed');

        $uow->registerAggregate($aggregate, $mockEventBus, $mockCallback);

        $aggregate->doSomething();
        $aggregate->doSomething();

        try {
            $uow->rollback(new \RuntimeException());
        } catch (\Exception $ex) {
            
        }      
    }
}
