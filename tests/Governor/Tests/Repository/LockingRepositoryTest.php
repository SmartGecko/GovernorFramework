<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Tests\Repository;

use Governor\Framework\EventHandling\EventBusInterface;
use Governor\Framework\Domain\AggregateRootInterface;
use Governor\Tests\Stubs\StubAggregate;
use Governor\Framework\UnitOfWork\CurrentUnitOfWork;
use Governor\Framework\UnitOfWork\DefaultUnitOfWork;
use Governor\Framework\Repository\LockingRepository;
use Governor\Framework\Repository\NullLockManager;
use Governor\Framework\Repository\LockManagerInterface;

/**
 * Description of LockingRepositoryTest
 *
 * @author david
 */
class LockingRepositoryTest extends \PHPUnit_Framework_TestCase
{

    private $testSubject;
    private $mockEventBus;
    private $lockManager;

    public function setUp()
    {
        $this->mockEventBus = $this->getMock(EventBusInterface::class);
        $this->lockManager = $this->getMock(NullLockManager::class); // new NullLockManager(); //spy(new OptimisticLockManager());

        $this->lockManager->expects($this->any())
            ->method('validateLock')
            ->will($this->returnValue(true));

        $this->testSubject = new InMemoryLockingRepository(StubAggregate::class,
            $this->mockEventBus, $this->lockManager);

        //testSubject = spy(testSubject);
        // some UoW is started somewhere, but not shutdown in the same test.
        while (CurrentUnitOfWork::isStarted()) {
            CurrentUnitOfWork::get()->rollback();
        }
    }

    public function testStoreNewAggregate()
    {
        DefaultUnitOfWork::startAndGet();
        $aggregate = new StubAggregate();
        $aggregate->doSomething();

        $this->lockManager->expects($this->once())
            ->method('obtainLock');

        $this->mockEventBus->expects($this->once())
            ->method('publish');

        $this->testSubject->add($aggregate);
        CurrentUnitOfWork::commit();
    }

    public function testLoadAndStoreAggregate()
    {
        DefaultUnitOfWork::startAndGet();
        $aggregate = new StubAggregate();
        $aggregate->doSomething();

        /*$this->lockManager->expects($this->exactly(2))
            ->method('obtainLock')
            ->with($this->equalTo($aggregate->getIdentifier()));

        $this->lockManager->expects($this->exactly(2))
            ->method('releaseLock')
            ->with($this->equalTo($aggregate->getIdentifier()));*/

        $this->testSubject->add($aggregate);

        CurrentUnitOfWork::commit();

        DefaultUnitOfWork::startAndGet();
        $loadedAggregate = $this->testSubject->load($aggregate->getIdentifier(),
            0);
        //verify(lockManager).obtainLock(aggregate.getIdentifier());

        $loadedAggregate->doSomething();
        CurrentUnitOfWork::commit();

      /*  $lockManager = Phake::mock(get_class($this->lockManager));

        \Phake::inOrder(
        \Phake::verify($lockManager)->validateLock(),
        \Phake::verify($lockManager)->releaseLock()
        );*/

        //InOrder inOrder = inOrder(lockManager);
        // inOrder.verify(lockManager, atLeastOnce()).validateLock(loadedAggregate);
        // verify(mockEventBus, times(2)).publish(any(DomainEventMessage.class));
        // inOrder.verify(lockManager).releaseLock(loadedAggregate.getIdentifier());
    }

    /*
      @SuppressWarnings({"ThrowableInstanceNeverThrown"})
      @Test
      public void testLoadAndStoreAggregate_LockReleasedOnException() {
      DefaultUnitOfWork.startAndGet();
      StubAggregate aggregate = new StubAggregate();
      aggregate.doSomething();
      testSubject.add(aggregate);
      verify(lockManager).obtainLock(aggregate.getIdentifier());
      CurrentUnitOfWork.commit();
      verify(lockManager).releaseLock(aggregate.getIdentifier());
      reset(lockManager);

      DefaultUnitOfWork.startAndGet();
      StubAggregate loadedAggregate = testSubject.load(aggregate.getIdentifier(), 0L);
      verify(lockManager).obtainLock(aggregate.getIdentifier());

      CurrentUnitOfWork.get().registerListener(new UnitOfWorkListenerAdapter() {
      @Override
      public void onPrepareCommit(UnitOfWork unitOfWork, Set<AggregateRoot> aggregateRoots,
      List<EventMessage> events) {
      throw new RuntimeException("Mock Exception");
      }
      });
      try {
      CurrentUnitOfWork.commit();
      fail("Expected exception to be thrown");
      } catch (RuntimeException e) {
      assertEquals("Mock Exception", e.getMessage());
      }

      // make sure the lock is released
      verify(lockManager).releaseLock(loadedAggregate.getIdentifier());
      }

      @SuppressWarnings({"ThrowableInstanceNeverThrown"})
      @Test
      public void testLoadAndStoreAggregate_PessimisticLockReleasedOnException() {
      lockManager = spy(new PessimisticLockManager());
      testSubject = new InMemoryLockingRepository(lockManager);
      testSubject.setEventBus(mockEventBus);
      testSubject = spy(testSubject);

      // we do the same test, but with a pessimistic lock, which has a different way of "re-acquiring" a lost lock
      DefaultUnitOfWork.startAndGet();
      StubAggregate aggregate = new StubAggregate();
      aggregate.doSomething();
      testSubject.add(aggregate);
      verify(lockManager).obtainLock(aggregate.getIdentifier());
      CurrentUnitOfWork.commit();
      verify(lockManager).releaseLock(aggregate.getIdentifier());
      reset(lockManager);

      DefaultUnitOfWork.startAndGet();
      StubAggregate loadedAggregate = testSubject.load(aggregate.getIdentifier(), 0L);
      verify(lockManager).obtainLock(aggregate.getIdentifier());

      CurrentUnitOfWork.get().registerListener(new UnitOfWorkListenerAdapter() {
      @Override
      public void onPrepareCommit(UnitOfWork unitOfWork, Set<AggregateRoot> aggregateRoots,
      List<EventMessage> events) {
      throw new RuntimeException("Mock Exception");
      }
      });

      try {
      CurrentUnitOfWork.commit();
      fail("Expected exception to be thrown");
      } catch (RuntimeException e) {
      assertEquals("Mock Exception", e.getMessage());
      }

      // make sure the lock is released
      verify(lockManager).releaseLock(loadedAggregate.getIdentifier());
      }

      @Test
      public void testSaveAggregate_RefusedDueToLackingLock() {
      lockManager = spy(new PessimisticLockManager());
      testSubject = new InMemoryLockingRepository(lockManager);
      testSubject.setEventBus(mockEventBus);
      testSubject = spy(testSubject);
      EventBus eventBus = mock(EventBus.class);

      DefaultUnitOfWork.startAndGet();
      StubAggregate aggregate = new StubAggregate();
      aggregate.doSomething();
      testSubject.add(aggregate);
      CurrentUnitOfWork.commit();

      DefaultUnitOfWork.startAndGet();
      StubAggregate loadedAggregate = testSubject.load(aggregate.getIdentifier(), 0L);
      loadedAggregate.doSomething();
      CurrentUnitOfWork.commit();

      // this tricks the UnitOfWork to save this aggregate, without loading it.
      DefaultUnitOfWork.startAndGet();
      CurrentUnitOfWork.get().registerAggregate(loadedAggregate,
      eventBus,
      new SaveAggregateCallback<StubAggregate>() {
      @Override
      public void save(StubAggregate aggregate) {
      testSubject.doSave(aggregate);
      }
      });
      loadedAggregate.doSomething();
      try {
      CurrentUnitOfWork.commit();
      fail("This should have failed due to lacking lock");
      } catch (ConcurrencyException e) {
      // that's ok
      }
      } */
}

class InMemoryLockingRepository extends LockingRepository
{

    private $saveCount;
    private $store = array();

    public function __construct($className, EventBusInterface $eventBus,
        LockManagerInterface $lockManager)
    {
        parent::__construct($className, $eventBus, $lockManager);
    }

    protected function doDeleteWithLock(AggregateRootInterface $aggregate)
    {
        unset($this->store[$aggregate->getIdentifier()]);
        $this->saveCount++;
    }

    protected function doLoad($id, $expectedVersion)
    {
        return $this->store[$id];
    }

    protected function doSaveWithLock(AggregateRootInterface $aggregate)
    {
        $this->store[$aggregate->getIdentifier()] = $aggregate;
        $this->saveCount++;
    }

    public function getSaveCount()
    {
        return $this->saveCount;
    }

    public function resetSaveCount()
    {
        $this->saveCount = 0;
    }

}
