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

namespace Governor\Framework\EventSourcing;

use Governor\Framework\Repository\LockingRepository;
use Governor\Framework\Domain\AggregateRootInterface;
use Governor\Framework\EventHandling\EventBusInterface;
use Governor\Framework\Repository\LockManagerInterface;
use Governor\Framework\EventStore\EventStoreInterface;
use Governor\Framework\Repository\AggregateNotFoundException;
use Governor\Framework\EventStore\EventStreamNotFoundException;
use Governor\Framework\UnitOfWork\CurrentUnitOfWork;

/**
 * Description of EventSourcingRepository
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a> 
 */
class EventSourcingRepository extends LockingRepository
{
    /**     
     * @var EventStoreInterface
     */
    private $eventStore;
    
    /**    
     * @var AggregateFactoryInterface
     */
    private $factory;
    
    /**     
     * @var ConflictResolverInterface
     */
    private $conflictResolver;
    
    /**     
     * @var EventStreamDecoratorInterface[] 
     */
    private $eventStreamDecorators = array();

    /**
     * Creates a new EventSourcingRepository with the given parameters.
     * 
     * @param string $className
     * @param EventBusInterface $eventBus
     * @param LockManagerInterface $lockManager
     * @param EventStoreInterface $eventStore
     * @param AggregateFactoryInterface $factory
     */
    public function __construct($className, EventBusInterface $eventBus,
        LockManagerInterface $lockManager, EventStoreInterface $eventStore,
        AggregateFactoryInterface $factory = null)
    {
        $this->validateEventSourcedAggregate($className);

        parent::__construct($className, $eventBus, $lockManager);
        $this->eventStore = $eventStore;
        $this->factory = null === $factory ? new GenericAggregateFactory($className) : $factory;
        $this->conflictResolver = null;
    }

    protected function doDeleteWithLock(AggregateRootInterface $aggregate)
    {
        $this->doSaveWithLock($aggregate);
    }

    protected function doLoad($id, $expectedVersion)
    {
        try {
            $events = $this->eventStore->readEvents($this->getTypeIdentifier(),
                $id);
        } catch (EventStreamNotFoundException $ex) {
            throw new AggregateNotFoundException($id, "The aggregate was not found", $ex);
        }

        foreach ($this->eventStreamDecorators as $decorator) {
            $events = $decorator->decorateForRead($this->getTypeIdentifier(),
                $id, $events);
        }
        
        $aggregate = $this->factory->createAggregate($id, $events->peek());
        $unseenEvents = array();
                
        $aggregate->initializeState(new CapturingEventStream($events,
            $unseenEvents, $expectedVersion));
        if ($aggregate->isDeleted()) {
            throw new AggregateDeletedException($id);
        }

        CurrentUnitOfWork::get()->registerListener(new ConflictResolvingListener($aggregate, $unseenEvents, $this->conflictResolver));

        return $aggregate;
    }

    protected function doSaveWithLock(AggregateRootInterface $aggregate)
    {
        $eventStream = $aggregate->getUncommittedEvents();
        $iterator = new \ArrayIterator(array_reverse($this->eventStreamDecorators));

        while ($iterator->valid()) {
            $eventStream = $iterator->current()->decorateForAppend($this->getTypeIdentifier(),
                $aggregate, $eventStream);
            $iterator->next();
        }
                
        $this->eventStore->appendEvents($this->getTypeIdentifier(), $eventStream);
    }

    private function validateEventSourcedAggregate($className)
    {
        $reflClass = new \ReflectionClass($className);

        if (!$reflClass->implementsInterface('Governor\Framework\EventSourcing\EventSourcedAggregateRootInterface')) {
            throw new \InvalidArgumentException(sprintf("EventSourcingRepository aggregates need to implements EventSourcedAggregateRootInterface, %s does not.",
                $className));
        }
    }

    /**
     * Returns the type identifier of the aggregates in this repository.
     * 
     * @return string
     * @throws \RuntimeException
     */
    public function getTypeIdentifier()
    {
        if (null === $this->factory) {
            throw new \RuntimeException("Either an aggregate factory must be configured (recommended), " .
                "or the getTypeIdentifier() method must be overridden.");
        }

        return $this->factory->getTypeIdentifier();
    }

    /**
     * Sets the conflict resolver to use for this repository. If not set (or <code>null</code>), the repository will
     * throw an exception if any unexpected changes appear in loaded aggregates.
     *
     * @param ConflictResolverInterface $conflictResolver The conflict resolver to use for this repository
     */
    public function setConflictResolver(ConflictResolverInterface $conflictResolver)
    {
        $this->conflictResolver = $conflictResolver;
    }

    protected function validateOnLoad(AggregateRootInterface $object,
        $expectedVersion)
    {
        if (null === $this->conflictResolver) {
            parent::validateOnLoad($object, $expectedVersion);
        }
    }

}
