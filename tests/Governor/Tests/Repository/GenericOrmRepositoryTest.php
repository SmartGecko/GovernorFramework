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

namespace Governor\Tests\Repository;

use Ramsey\Uuid\Uuid;
use Doctrine\ORM\EntityManager;
use Governor\Framework\Repository\AggregateNotFoundException;
use Governor\Framework\Repository\ConflictingAggregateVersionException;
use Governor\Framework\Domain\AbstractAggregateRoot;
use Governor\Framework\UnitOfWork\DefaultUnitOfWork;
use Governor\Framework\UnitOfWork\CurrentUnitOfWork;
use Governor\Framework\EventHandling\EventBusInterface;
use Governor\Framework\Repository\GenericOrmRepository;
use Governor\Framework\Repository\NullLockManager;

/**
 * Description of GenericDoctrineRepositoryTest
 *
 * @author david
 */
class GenericOrmRepositoryTest extends \PHPUnit_Framework_TestCase
{

    private $mockEntityManager;
    private $mockEventBus;
    private $testSubject; // GenericDoctrineRepository
    private $aggregateId;
    private $aggregate; // StubDoctrineAggregate

    public function setUp()
    {
        $this->mockEntityManager = $this->getMock(EntityManager::class,
            array('find', 'flush', 'persist', 'remove'), array(), '', false);
        $this->mockEventBus = $this->getMock(EventBusInterface::class);
        $this->testSubject = new GenericOrmRepository(StubDoctrineAggregate::class,
             $this->mockEventBus, new NullLockManager(), $this->mockEntityManager);

        $this->aggregateId = "123";
        $this->aggregate = new StubDoctrineAggregate($this->aggregateId);
        DefaultUnitOfWork::startAndGet();
    }

    public function tearDown()
    {
        while (CurrentUnitOfWork::isStarted()) {
            CurrentUnitOfWork::get()->rollback();
        }
    }

    public function testLoadAggregate()
    {
        $this->mockEntityManager->expects($this->once())
            ->method('find')
            ->with($this->equalTo(StubDoctrineAggregate::class),
                $this->equalTo('123'))
            ->will($this->returnValue($this->aggregate));

        $actualResult = $this->testSubject->load($this->aggregateId);
        $this->assertSame($this->aggregate, $actualResult);
    }

    public function testLoadAggregate_NotFound()
    {
        $aggregateIdentifier = Uuid::uuid1()->toString();
        try {
            $this->testSubject->load($aggregateIdentifier);
            $this->fail("Expected AggregateNotFoundException");
        } catch (AggregateNotFoundException $ex) {
            $this->assertEquals($aggregateIdentifier, $ex->getAggregateId());
        }
    }

    public function testLoadAggregate_WrongVersion()
    {
        $this->mockEntityManager->expects($this->once())
            ->method('find')
            ->with($this->equalTo(StubDoctrineAggregate::class),
                $this->equalTo('123'))
            ->will($this->returnValue($this->aggregate));

        try {
            $this->testSubject->load($this->aggregateId, 2);
            $this->fail("Expected ConflictingAggregateVersionException");
        } catch (ConflictingAggregateVersionException $ex) {
            $this->assertEquals(2, $ex->getExpectedVersion());
            $this->assertEquals(0, $ex->getActualVersion());
        }
    }

    public function testPersistAggregate_DefaultFlushMode()
    {
        $this->mockEntityManager->expects($this->once())
            ->method('flush');

        $reflection = new \ReflectionClass(GenericOrmRepository::class);
        $method = $reflection->getMethod('doSaveWithLock');
        $method->setAccessible(true);

        $method->invokeArgs($this->testSubject, array($this->aggregate));
        
        $method2 = $reflection->getMethod('postSave');
        $method2->setAccessible(true);

        $method2->invokeArgs($this->testSubject, array($this->aggregate));
    }

    public function testPersistAggregate_ExplicitFlushModeOn()
    {
        $this->mockEntityManager->expects($this->once())
            ->method('flush');

        $reflection = new \ReflectionClass(GenericOrmRepository::class);
        $method = $reflection->getMethod('doSaveWithLock');
        $method->setAccessible(true);

        $method->invokeArgs($this->testSubject, array($this->aggregate));
        
        $method2 = $reflection->getMethod('postSave');
        $method2->setAccessible(true);

        $method2->invokeArgs($this->testSubject, array($this->aggregate));
    }

    public function testPersistAggregate_ExplicitFlushModeOff()
    {
        $this->testSubject->setForceFlushOnSave(false);
        $this->mockEntityManager->expects($this->never())
            ->method('flush');

        $this->assertFalse($this->testSubject->isForceFlushOnSave());

        $reflection = new \ReflectionClass(GenericOrmRepository::class);
        $method = $reflection->getMethod('doSaveWithLock');
        $method->setAccessible(true);

        $method->invokeArgs($this->testSubject, array($this->aggregate));
    }

    public function testRemoveAggregate_ExplicitFlushModeOn()
    {
        $this->mockEntityManager->expects($this->once())
            ->method('flush');

        $reflection = new \ReflectionClass(GenericOrmRepository::class);
        $method = $reflection->getMethod('doDeleteWithLock');
        $method->setAccessible(true);

        $method->invokeArgs($this->testSubject, array($this->aggregate));
    }

    public function testRemoveAggregate_ExplicitFlushModeOff()
    {
        $this->testSubject->setForceFlushOnSave(false);
        $this->mockEntityManager->expects($this->never())
            ->method('flush');

        $this->assertFalse($this->testSubject->isForceFlushOnSave());

        $reflection = new \ReflectionClass(GenericOrmRepository::class);
        $method = $reflection->getMethod('doDeleteWithLock');
        $method->setAccessible(true);

        $method->invokeArgs($this->testSubject, array($this->aggregate));
    }

}

class StubDoctrineAggregate extends AbstractAggregateRoot
{

    private $identifier;

    function __construct($identifier)
    {
        $this->identifier = $identifier;
    }

    public function getIdentifier()
    {
        return $this->identifier;
    }

    public function getVersion()
    {
        return 0;
    }

}
