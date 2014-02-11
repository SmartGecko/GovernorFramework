<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Repository;

use Governor\Framework\Domain\AggregateRootInterface;

/**
 * Description of NullLockManager
 *
 * @author david
 */
class NullLockManager implements LockManagerInterface
{

    public function obtainLock($aggregateIdentifier)
    {
        
    }

    public function releaseLock($aggregateIdentifier)
    {
        
    }

    public function validateLock(AggregateRootInterface $aggregate)
    {
        return true;
    }

}
