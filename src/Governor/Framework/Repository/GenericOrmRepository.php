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
use Governor\Framework\EventHandling\EventBusInterface;
use Doctrine\ORM\EntityManager;

/**
 * Description of GenericDoctrineRepository
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a> 
 */
class GenericOrmRepository extends LockingRepository
{
    /**     
     * @var EntityManager
     */
    private $entityManager;
    
    /**     
     * @var boolean
     */
    private $forceFlushOnSave = true;

    public function __construct($className, EventBusInterface $eventBus,
        LockManagerInterface $lockManager, EntityManager $entityManager)
    {
        parent::__construct($className, $eventBus, $lockManager);
        $this->entityManager = $entityManager;
    }

    protected function doDeleteWithLock(AggregateRootInterface $aggregate)
    {
        $this->entityManager->remove($aggregate);

        if ($this->forceFlushOnSave) {
            $this->entityManager->flush();
        }
    }

    protected function doSaveWithLock(AggregateRootInterface $aggregate)
    {
        $this->entityManager->persist($aggregate);
    }

    protected function postSave(AggregateRootInterface $object)
    {                
        if ($this->forceFlushOnSave) {
            $this->entityManager->flush();
        }
    }

    protected function doLoad($id, $expectedVersion)
    {
        $aggregate = $this->entityManager->find($this->getClass(), $id);

        if (null === $aggregate) {
            throw new AggregateNotFoundException($id,
            sprintf(
                "Aggregate [%s] with identifier [%s] not found",
                $this->getClass(), $id));
        } else if (null !== $expectedVersion && null !== $aggregate->getVersion() && $expectedVersion !== $aggregate->getVersion()) {
            throw new ConflictingAggregateVersionException($id,
            $expectedVersion, $aggregate->getVersion());
        }
        
        return $aggregate;
    }

    /**
     * 
     * @return boolean
     */
    public function isForceFlushOnSave()
    {
        return $this->forceFlushOnSave;
    }

    /**
     * 
     * @param boolean $forceFlushOnSave
     */
    public function setForceFlushOnSave($forceFlushOnSave)
    {
        $this->forceFlushOnSave = $forceFlushOnSave;
    }   

}
