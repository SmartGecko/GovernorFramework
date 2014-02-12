<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Repository;

/**
 * Description of OptimitsticLockManager
 *
 * @author 255196
 */
class OptimitsticLockManager implements LockManagerInterface
{
    public function obtainLock($aggregateIdentifier)
    {
        
    }

    public function releaseLock($aggregateIdentifier)
    {
        
    }

    public function validateLock(\Governor\Framework\Domain\AggregateRootInterface $aggregate)
    {
        
    }

}
