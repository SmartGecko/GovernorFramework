<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Repository;

use Governor\Framework\Domain\AggregateRootInterface;

/**
 * LockManagerInterface implementation that does not perform any locking.
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
class NullLockManager implements LockManagerInterface
{

    /**
     * @inheritdoc
     */
    public function obtainLock($aggregateIdentifier)
    {
        
    }

    /**
     * @inheritdoc
     */
    public function releaseLock($aggregateIdentifier)
    {
        
    }

    /**
     * @inheritdoc
     */
    public function validateLock(AggregateRootInterface $aggregate)
    {
        return true;
    }

}
