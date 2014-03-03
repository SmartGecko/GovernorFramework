<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Repository;

use Governor\Framework\Domain\AggregateRootInterface;

/**
 * Description of OptimitsticLockManager
 *
 * @author 255196
 */
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
