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

use Governor\Framework\Repository\GenericOrmRepository;
use Governor\Framework\EventHandling\EventBusInterface;
use Governor\Framework\Repository\LockManagerInterface;
use Governor\Framework\EventStore\EventStoreInterface;
use Governor\Framework\Domain\AggregateRootInterface;
use Doctrine\ORM\EntityManager;

/**
 * Description of HybridDoctrineRepository
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a> 
 */
class HybridDoctrineRepository extends GenericOrmRepository
{

    /**
     * @var EventStoreInterface 
     */
    private $eventStore;
    
    /**     
     * @var string
     */
    private $aggregateTypeIdentifier;

    public function __construct($className, EventBusInterface $eventBus,
        LockManagerInterface $lockManager, EntityManager $entityManager,
        EventStoreInterface $eventStore)
    {
        parent::__construct($className, $eventBus, $lockManager, $entityManager);
        $this->eventStore = $eventStore;
        
        $reflClass = new \ReflectionClass($className);
        $this->aggregateTypeIdentifier = $reflClass->getShortName();
    }

    protected function doDeleteWithLock(AggregateRootInterface $aggregate)
    {
        if (null !== $this->eventStore) {
            $this->eventStore->appendEvents($this->getTypeIdentifier(),
                $aggregate->getUncommittedEvents());
        }

        parent::doDeleteWithLock($aggregate);
    }

    protected function doSaveWithLock(AggregateRootInterface $aggregate)
    {
        if (null !== $this->eventStore) {
            $this->eventStore->appendEvents($this->getTypeIdentifier(),
                $aggregate->getUncommittedEvents());
        }

        parent::doSaveWithLock($aggregate);
    }

    /**
     * Returns the type identifier to use when appending events in the event store. Default to the simple class name of
     * the aggregateType provided in the constructor.
     *
     * @return string the type identifier to use when appending events in the event store.
     */
    protected function getTypeIdentifier()
    {
        return $this->aggregateTypeIdentifier;
    }

}
