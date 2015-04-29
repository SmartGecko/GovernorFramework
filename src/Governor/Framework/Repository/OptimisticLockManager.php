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

/**
 * Description of OptimitsticLockManager
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a> 
 */
// TODO this should be moved to the redis extension since this cannot occur without workers
class OptimisticLockManager implements LockManagerInterface
{

    private $locks = array();

    public function obtainLock($aggregateIdentifier)
    {
        if (!array_key_exists($aggregateIdentifier, $this->locks)) {
            $this->locks[$aggregateIdentifier] = new OptimisticLock();
        }
        
        $lock = $this->locks[$aggregateIdentifier];
        if (!$lock->lock()) {
            unset($this->locks[$aggregateIdentifier]);
        }
    }

    public function releaseLock($aggregateIdentifier)
    {
        if (array_key_exists($aggregateIdentifier, $this->locks)) {
            $lock = $this->locks[$aggregateIdentifier];
            $lock->unlock($aggregateIdentifier, $this->locks);
        }
    }

    public function validateLock(AggregateRootInterface $aggregate)
    {
        if (array_key_exists($aggregate->getIdentifier(), $this->locks)) {
            $lock = $this->locks[$aggregate->getIdentifier()];            
            return $lock->validate($aggregate);
        }

        return true;
    }

}

class OptimisticLock
{

    private $versionNumber;
    private $lockCount = 0;
    private $closed = false;

    public function validate(AggregateRootInterface $aggregate)
    {
        $lastCommitedScn = $aggregate->getVersion();   
        
        if (null === $this->versionNumber || $this->versionNumber === $lastCommitedScn) {             
            $last = (null === $lastCommitedScn) ? 0 : $lastCommitedScn;            
            $this->versionNumber = $last;            
            return true;
        }
        
        return false;
    }

    public function lock()
    {
        if ($this->closed) {
            return false;
        }

        $this->lockCount++;
        return true;
    }

    public function unlock($aggregateIdentifier, &$locks)
    {
        if ($this->lockCount !== 0) {
            $this->lockCount--;
        }
        
        if ($this->lockCount === 0) {
            $this->closed = true;
            unset($locks[$aggregateIdentifier]);
        }
    }

}
