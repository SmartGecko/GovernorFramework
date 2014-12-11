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

namespace Governor\Tests\UnitOfWork;

use Governor\Framework\Domain\GenericEventMessage;
use Governor\Framework\EventHandling\EventListenerInterface;
use Governor\Framework\Domain\AggregateRootInterface;
use Governor\Framework\EventHandling\EventBusInterface;
use Governor\Framework\UnitOfWork\UnitOfWorkListenerAdapter;
use Governor\Framework\UnitOfWork\UnitOfWorkInterface;
use Governor\Framework\UnitOfWork\CurrentUnitOfWork;
use Governor\Framework\UnitOfWork\DefaultUnitOfWork;
use Governor\Framework\UnitOfWork\SaveAggregateCallbackInterface;
use Governor\Framework\UnitOfWork\UnitOfWorkListenerInterface;
use Governor\Framework\UnitOfWork\TransactionManagerInterface;

/**
 * Description of DefaultUnitOfWorkTest
 *
 * @author david
 */
class DefaultUnitOfWorkTest extends \PHPUnit_Framework_TestCase
{

    private $testSubject;
    private $mockEventBus;
    private $mockAggregateRoot;
    private $mockLogger;
    private $event1;
    private $event2;
    private $listener1;
    private $listener2;
    private $callback1;
    private $callback2;

    public function setUp()
    {
        while (CurrentUnitOfWork::isStarted()) {
            CurrentUnitOfWork::get()->rollback();
        }

        $this->event1 = new GenericEventMessage(new TestMessage(1));
        $this->event2 = new GenericEventMessage(new TestMessage(1));

        $this->testSubject = new DefaultUnitOfWork();
        $this->mockEventBus = \Phake::mock(EventBusInterface::class);
        $this->mockAggregateRoot = \Phake::mock(AggregateRootInterface::class);
        $this->listener1 = \Phake::mock(EventListenerInterface::class);
        $this->listener2 = \Phake::mock(EventListenerInterface::class);
        $this->callback1 = \Phake::mock(SaveAggregateCallbackInterface::class);
        $this->callback2 = \Phake::mock(SaveAggregateCallbackInterface::class);

        $self = $this;

        \Phake::when($this->callback1)->save($this->mockAggregateRoot)->thenGetReturnByLambda(function ($aggregate) use($self) {
            CurrentUnitOfWork::get()->publishEvent($self->event1,
                    $self->mockEventBus);
            CurrentUnitOfWork::get()->publishEvent($self->event2,
                    $self->mockEventBus);
        });

        \Phake::when($this->mockEventBus)->publish(\Phake::anyParameters())->thenGetReturnByLambda(function ($event) use($self) {
            $self->listener1->handle($event[0]);
            $self->listener2->handle($event[0]);

            $self->listener1->handle($event[1]);
            $self->listener2->handle($event[1]);
        });
    }

    public function tearDown()
    {
        $this->assertFalse(CurrentUnitOfWork::isStarted(),
                "A UnitOfWork was not properly cleared");
    }

    public function testTransactionBoundUnitOfWorkLifecycle()
    {
        $mockListener = \Phake::mock(UnitOfWorkListenerInterface::class);
        $mockTransactionManager = \Phake::mock(TransactionManagerInterface::class);

        \Phake::when($mockTransactionManager)->startTransaction()->thenReturn(new \stdClass());

        $uow = DefaultUnitOfWork::startAndGet($mockTransactionManager);
        $uow->registerListener($mockListener);

        \Phake::verify($mockTransactionManager)->startTransaction();
        \Phake::verifyNoInteraction($mockListener);

        $uow->commit();

        \Phake::inOrder(
                \Phake::verify($mockListener)->onPrepareCommit(\Phake::anyParameters()),
                \Phake::verify($mockListener)->onPrepareTransactionCommit(\Phake::equalTo($uow),
                        \Phake::anyParameters()),
                \Phake::verify($mockTransactionManager)->commitTransaction(\Phake::anyParameters()),
                \Phake::verify($mockListener)->afterCommit(\Phake::equalTo($uow)),
                \Phake::verify($mockListener)->onCleanup(\Phake::equalTo($uow))
        );
    }

    public function testTransactionBoundUnitOfWorkLifecycle_Rollback()
    {
        $mockListener = \Phake::mock(UnitOfWorkListenerInterface::class);
        $mockTransactionManager = \Phake::mock(TransactionManagerInterface::class);

        \Phake::when($mockTransactionManager)->startTransaction()->thenReturn(new \stdClass());

        $uow = DefaultUnitOfWork::startAndGet($mockTransactionManager);
        $uow->registerListener($mockListener);

        \Phake::verify($mockTransactionManager)->startTransaction();
        \Phake::verifyNoInteraction($mockListener);

        $uow->rollback();

        \Phake::inOrder(
                \Phake::verify($mockTransactionManager)->rollbackTransaction(\Phake::anyParameters()),
                \Phake::verify($mockListener)->onRollback(\Phake::anyParameters()),
                \Phake::verify($mockListener)->onCleanup(\Phake::equalTo($uow))
        );
    }

    public function testUnitOfWorkRegistersListenerWithParent()
    {
        $parentUoW = \Phake::mock(UnitOfWorkInterface::class);
        CurrentUnitOfWork::set($parentUoW);
        $innerUow = DefaultUnitOfWork::startAndGet();
        $innerUow->rollback();
        $parentUoW->rollback();
        CurrentUnitOfWork::clear($parentUoW);

        \Phake::verify($parentUoW)->registerListener(\Phake::anyParameters()); //UnitOfWorkListener       
    }

    public function testInnerUnitOfWorkRolledBackWithOuter()
    {
        $isRolledBack = false;
        $outer = DefaultUnitOfWork::startAndGet();
        $inner = DefaultUnitOfWork::startAndGet();

        $inner->registerListener(new RollbackTestAdapter(function (UnitOfWorkInterface $uow, \Exception $ex = null) use (&$isRolledBack) {
            $isRolledBack = true;
        }));


        $inner->commit();
        $outer->rollback();

        $this->assertTrue($isRolledBack,
                "The inner UoW wasn't properly rolled back");
        $this->assertFalse(CurrentUnitOfWork::isStarted(),
                "The UnitOfWork haven't been correctly cleared");
    }

    public function testInnerUnitOfWorkCommittedBackWithOuter()
    {
        $isCommitted = false;
        $outer = DefaultUnitOfWork::startAndGet();
        $inner = DefaultUnitOfWork::startAndGet();

        $inner->registerListener(new AfterCommitTestAdapter(function (UnitOfWorkInterface $uow) use (&$isCommitted) {
            $isCommitted = true;
        }));

        $inner->commit();
        $this->assertFalse($isCommitted,
                "The inner UoW was committed prematurely");
        $outer->commit();
        $this->assertTrue($isCommitted,
                "The inner UoW wasn't properly committed");
        $this->assertFalse(CurrentUnitOfWork::isStarted(),
                "The UnitOfWork haven't been correctly cleared");
    }

    public function testSagaEventsDoNotOvertakeRegularEvents()
    {
        $this->testSubject->start();
        $self = $this;

        \Phake::when($this->listener1)->handle($this->event1)->thenGetReturnByLambda(function ($event) use($self) {
            $uow = new DefaultUnitOfWork();
            $uow->start();
            $uow->registerAggregate($self->mockAggregateRoot,
                    $self->mockEventBus, $self->callback2);
            $uow->commit();

            return null;
        });

        $this->testSubject->registerAggregate($this->mockAggregateRoot,
                $this->mockEventBus, $this->callback1);

        $this->testSubject->commit();

        \Phake::inOrder(
                \Phake::verify($this->listener1, \Phake::times(1))->handle($this->event1),
                \Phake::verify($this->listener2, \Phake::times(1))->handle($this->event1),
                \Phake::verify($this->listener1, \Phake::times(1))->handle($this->event2),
                \Phake::verify($this->listener2, \Phake::times(1))->handle($this->event2)
        );
    }

    public function testUnitOfWorkRolledBackOnCommitFailure_ErrorOnPrepareCommit()
    {
        $mockListener = \Phake::mock(UnitOfWorkListenerInterface::class);
        \Phake::when($mockListener)->onPrepareCommit(\Phake::anyParameters())->thenThrow(new \RuntimeException('phpunit'));

        $this->testSubject->registerListener($mockListener);
        $this->testSubject->start();

        try {
            $this->testSubject->commit();
            $this->fail("Expected exception");
        } catch (\Exception $ex) {
            $this->assertInstanceOf('\RuntimeException', $ex);
            $this->assertEquals('phpunit', $ex->getMessage());
        }

        \Phake::verify($mockListener)->onRollback(\Phake::anyParameters());
        \Phake::verify($mockListener, \Phake::never())->afterCommit(\Phake::anyParameters());
        \Phake::verify($mockListener)->onCleanup(\Phake::anyParameters());
    }

    public function testUnitOfWorkRolledBackOnCommitFailure_ErrorOnCommitAggregate()
    {
        $mockListener = \Phake::mock(UnitOfWorkListenerInterface::class);
        \Phake::when($this->callback1)->save(\Phake::anyParameters())->thenThrow(new \RuntimeException('phpunit'));

        $this->testSubject->registerListener($mockListener);
        $this->testSubject->registerAggregate($this->mockAggregateRoot,
                $this->mockEventBus, $this->callback1);
        $this->testSubject->start();

        try {
            $this->testSubject->commit();
            $this->fail("Expected exception");
        } catch (\RuntimeException $ex) {
            $this->assertInstanceOf('\RuntimeException', $ex,
                    "Got an exception, but the wrong one");
            $this->assertEquals('phpunit', $ex->getMessage(),
                    "Got an exception, but the wrong one");
        }

        \Phake::verify($mockListener)->onPrepareCommit(\Phake::anyParameters());
        \Phake::verify($mockListener)->onRollback(\Phake::anyParameters());
        \Phake::verify($mockListener, \Phake::never())->afterCommit(\Phake::anyParameters());
        \Phake::verify($mockListener)->onCleanup(\Phake::anyParameters());
    }

    public function testUnitOfWorkRolledBackOnCommitFailure_ErrorOnDispatchEvents()
    {
        $mockListener = \Phake::mock(UnitOfWorkListenerInterface::class);

        \Phake::when($mockListener)->onEventRegistered(\Phake::anyParameters())->thenReturn(new GenericEventMessage(new TestMessage(1)));
        \Phake::when($this->mockEventBus)->publish(\Phake::anyParameters())->thenThrow(new \RuntimeException('phpunit'));

        $this->testSubject->start();
        $this->testSubject->registerListener($mockListener);
        $this->testSubject->publishEvent(new GenericEventMessage(new TestMessage(1)),
                $this->mockEventBus);

        try {
            $this->testSubject->commit();
            $this->fail("Expected exception");
        } catch (\RuntimeException $ex) {
            $this->assertInstanceOf('\RuntimeException', $ex,
                    "Got an exception, but the wrong one");
            $this->assertEquals('phpunit', $ex->getMessage(),
                    "Got an exception, but the wrong one");
        }

        \Phake::verify($mockListener)->onPrepareCommit(\Phake::anyParameters());
        \Phake::verify($mockListener)->onRollback(\Phake::anyParameters());
        \Phake::verify($mockListener, \Phake::never())->afterCommit(\Phake::anyParameters());
        \Phake::verify($mockListener)->onCleanup(\Phake::anyParameters());
    }

    public function testUnitOfWorkCleanupDelayedUntilOuterUnitOfWorkIsCleanedUp_InnerCommit()
    {
        $outerListener = \Phake::mock(UnitOfWorkListenerInterface::class);
        $innerListener = \Phake::mock(UnitOfWorkListenerInterface::class);

        $outer = DefaultUnitOfWork::startAndGet($this->mockLogger);
        $inner = DefaultUnitOfWork::startAndGet($this->mockLogger);

        $outer->registerListener($outerListener);
        $inner->registerListener($innerListener);
        $inner->commit();

        \Phake::verify($innerListener, \Phake::never())->afterCommit(\Phake::anyParameters());
        \Phake::verify($innerListener, \Phake::never())->onCleanup(\Phake::anyParameters());

        $outer->commit();

        \Phake::inOrder(
                \Phake::verify($innerListener)->afterCommit(\Phake::anyParameters()),
                \Phake::verify($outerListener)->afterCommit(\Phake::anyParameters()),
                \Phake::verify($innerListener)->onCleanup(\Phake::anyParameters()),
                \Phake::verify($outerListener)->onCleanup(\Phake::anyParameters())
        );
    }

    public function testUnitOfWorkCleanupDelayedUntilOuterUnitOfWorkIsCleanedUp_InnerRollback()
    {
        $outerListener = \Phake::mock(UnitOfWorkListenerInterface::class);
        $innerListener = \Phake::mock(UnitOfWorkListenerInterface::class);

        $outer = DefaultUnitOfWork::startAndGet();
        $inner = DefaultUnitOfWork::startAndGet();

        $inner->registerListener($innerListener);
        $outer->registerListener($outerListener);
        $inner->rollback();

        \Phake::verify($innerListener, \Phake::never())->afterCommit(\Phake::anyParameters());
        \Phake::verify($innerListener, \Phake::never())->onCleanup(\Phake::anyParameters());
        $outer->commit();

        \Phake::inOrder(
                \Phake::verify($innerListener)->onRollback(\Phake::anyParameters()),
                \Phake::verify($outerListener)->afterCommit(\Phake::anyParameters()),
                \Phake::verify($innerListener)->onCleanup(\Phake::anyParameters()),
                \Phake::verify($outerListener)->onCleanup(\Phake::anyParameters())
        );
    }

    public function testUnitOfWorkCleanupDelayedUntilOuterUnitOfWorkIsCleanedUp_InnerCommit_OuterRollback()
    {
        $outerListener = \Phake::mock(UnitOfWorkListenerInterface::class);
        $innerListener = \Phake::mock(UnitOfWorkListenerInterface::class);

        $outer = DefaultUnitOfWork::startAndGet();
        $inner = DefaultUnitOfWork::startAndGet();

        $inner->registerListener($innerListener);
        $outer->registerListener($outerListener);

        $inner->commit();

        \Phake::verify($innerListener, \Phake::never())->afterCommit(\Phake::anyParameters());
        \Phake::verify($innerListener, \Phake::never())->onCleanup(\Phake::anyParameters());
        $outer->rollback();

        \Phake::verify($outerListener, \Phake::never())->onPrepareCommit(\Phake::anyParameters());

        \Phake::inOrder(
                \Phake::verify($innerListener)->onPrepareCommit(\Phake::anyParameters()),
                \Phake::verify($innerListener)->onRollback(\Phake::anyParameters()),
                \Phake::verify($outerListener)->onRollback(\Phake::anyParameters()),
                \Phake::verify($innerListener)->onCleanup(\Phake::anyParameters()),
                \Phake::verify($outerListener)->onCleanup(\Phake::anyParameters())
        );
    }
}

class TestMessage
{

    private $text;

    function __construct($text)
    {
        $this->text = $text;
    }

    public function getText()
    {
        return $this->text;
    }

}

class RollbackTestAdapter extends UnitOfWorkListenerAdapter
{

    private $closure;

    public function __construct(\Closure $closure)
    {
        $this->closure = $closure;
    }

    public function onRollback(UnitOfWorkInterface $unitOfWork,
            \Exception $failureCause = null)
    {
        $cb = $this->closure;
        $cb($unitOfWork, $failureCause);
    }

}

class AfterCommitTestAdapter extends UnitOfWorkListenerAdapter
{

    private $closure;

    public function __construct(\Closure $closure)
    {
        $this->closure = $closure;
    }

    public function afterCommit(UnitOfWorkInterface $unitOfWork)
    {
        $cb = $this->closure;
        $cb($unitOfWork);
    }

}
