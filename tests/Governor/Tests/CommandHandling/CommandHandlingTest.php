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

namespace Governor\Tests\CommandHandling;

use Governor\Tests\Stubs\StubAggregate;
use Governor\Framework\UnitOfWork\DefaultUnitOfWork;
use Governor\Framework\UnitOfWork\CurrentUnitOfWork;
use Governor\Framework\Repository\NullLockManager;
use Governor\Framework\EventSourcing\EventSourcingRepository;
use Governor\Framework\EventSourcing\GenericAggregateFactory;
use Governor\Framework\EventStore\EventStoreInterface;
use Governor\Framework\EventHandling\EventBusInterface;

/**
 * Description of CommandHandlingTest
 *
 * @author david
 */
class CommandHandlingTest extends \PHPUnit_Framework_TestCase
{

    private $repository;
    private $aggregateIdentifier;
    private $mockEventBus;
    private $mockEventStore;    

    public function setUp()
    {
        $this->mockEventStore = new StubEventStore();
        $this->mockEventBus = $this->getMock(EventBusInterface::class);
        $this->repository = new EventSourcingRepository(StubAggregate::class,
            $this->mockEventBus, new NullLockManager(), $this->mockEventStore,
            new GenericAggregateFactory(StubAggregate::class));
        $this->aggregateIdentifier = "testAggregateIdentifier";        
    }

    public function testCommandHandlerLoadsSameAggregateTwice()
    {
        DefaultUnitOfWork::startAndGet();

        $stubAggregate = new StubAggregate($this->aggregateIdentifier);
        $stubAggregate->doSomething();
        $this->repository->add($stubAggregate);
        CurrentUnitOfWork::commit();

        DefaultUnitOfWork::startAndGet();
        $this->repository->load($this->aggregateIdentifier)->doSomething();
        $this->repository->load($this->aggregateIdentifier)->doSomething();
        CurrentUnitOfWork::commit();

        $es = $this->mockEventStore->readEvents("", $this->aggregateIdentifier);
        $this->assertTrue($es->hasNext());
        $this->assertEquals(0, $es->next()->getScn());
        $this->assertTrue($es->hasNext());
        $this->assertEquals(1, $es->next()->getScn());
        $this->assertTrue($es->hasNext());
        $this->assertEquals(2, $es->next()->getScn());
        $this->assertFalse($es->hasNext());
    }

}

class StubEventStore implements EventStoreInterface
{

    private $storedEvents = array();

    public function appendEvents($type,
        \Governor\Framework\Domain\DomainEventStreamInterface $events)
    {
        while ($events->hasNext()) {
            $this->storedEvents[] = $events->next();
        }
    }

    public function readEvents($type, $identifier)
    {
        return new \Governor\Framework\Domain\SimpleDomainEventStream($this->storedEvents);
    }

    public function setLogger(\Psr\Log\LoggerInterface $logger)
    {
        
    }

}
