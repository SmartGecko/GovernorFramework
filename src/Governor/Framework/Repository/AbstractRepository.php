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

namespace Governor\Framework\Repository;

use Governor\Framework\Domain\AggregateRootInterface;
use Governor\Framework\UnitOfWork\CurrentUnitOfWork;
use Governor\Framework\EventHandling\EventBusInterface;
use Governor\Framework\UnitOfWork\SaveAggregateCallbackInterface;

/**
 * Description of AbstractRepository
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a> 
 */
abstract class AbstractRepository implements RepositoryInterface
{
    /**
     * @var EventBusInterface 
     */
    private $eventBus;
    
    /**     
     * @var string
     */
    private $className;
    
    /**    
     * @var SaveAggregateCallbackInterface
     */
    private $saveAggregateCallback;

    public function __construct($className, EventBusInterface $eventBus)
    {
        $this->className = $className;
        $this->eventBus = $eventBus;

        $repos = $this;
        $this->saveAggregateCallback = new SimpleSaveAggregateCallback(function (AggregateRootInterface $aggregateRoot) use ($repos) {
            if ($aggregateRoot->isDeleted()) {
                $repos->doDelete($aggregateRoot);
            } else {
                $repos->doSave($aggregateRoot);
            }

            $aggregateRoot->commitEvents();
            if ($aggregateRoot->isDeleted()) {
                $repos->postDelete($aggregateRoot);
            } else {
                $repos->postSave($aggregateRoot);
            }
        });
    }

    public function load($id, $expectedVersion = null)
    {
        $object = $this->doLoad($id, $expectedVersion);
        $this->validateOnLoad($object, $expectedVersion);

        return CurrentUnitOfWork::get()->registerAggregate($object,
                        $this->eventBus, $this->saveAggregateCallback);
    }

    public function add(AggregateRootInterface $aggregateRoot)
    {
        if (null !== $aggregateRoot->getVersion()) {
            throw new \InvalidArgumentException("Only newly created (unpersisted) aggregates may be added.");
        }

        if (!$this->supportsClass(get_class($aggregateRoot))) {
            throw new \InvalidArgumentException(sprintf("This repository supports %s, but got %s",
                    $this->className, get_class($aggregateRoot)));
        }

        CurrentUnitOfWork::get()->registerAggregate($aggregateRoot,
                $this->eventBus, $this->saveAggregateCallback);
    }

    public function supportsClass($class)
    {
        $reflClass = new \ReflectionClass($class);

        if ($reflClass->name === $this->className || 
                $reflClass->isSubclassOf($this->className)) {
            return true;
        }

        return false;
    }

    public function getClass()
    {
        return $this->className;
    }

    protected function validateOnLoad(AggregateRootInterface $object,
            $expectedVersion)
    {
        if (null !== $expectedVersion && null !== $object->getVersion() &&
                $expectedVersion !== $object->getVersion()) {
            throw new ConflictingAggregateVersionException($object->getIdentifier(),
            $expectedVersion, $object->getVersion());
        }
    }

    protected abstract function doSave(AggregateRootInterface $object);

    protected abstract function doLoad($id, $expectedVersion);

    protected abstract function doDelete(AggregateRootInterface $object);

    /**
     * Perform action that needs to be done directly after updating an aggregate and committing the aggregate's
     * uncommitted events.
     *
     * @param AggregateRootInterface $aggregate The aggregate instance being saved
     */
    protected function postSave(AggregateRootInterface $aggregate)
    {
        
    }

    /**
     * Perform action that needs to be done directly after deleting an aggregate and committing the aggregate's
     * uncommitted events.
     *
     * @param AggregateRootInterface $aggregate The aggregate instance being saved
     */
    protected function postDelete(AggregateRootInterface $aggregate)
    {
        
    }

}
