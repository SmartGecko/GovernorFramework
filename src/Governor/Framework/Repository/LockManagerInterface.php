<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Repository;

use Governor\Framework\Domain\AggregateRootInterface;

/**
 *
 * @author david
 */
interface LockManagerInterface
{

    /**
     * Make sure that the current thread holds a valid lock for the given aggregate.
     *
     * @param aggregate the aggregate to validate the lock for
     * @return true if a valid lock is held, false otherwise
     */
    public function validateLock(AggregateRootInterface $aggregate);

    /**
     * Obtain a lock for an aggregate with the given <code>aggregateIdentifier</code>. Depending on the strategy, this
     * method may return immediately or block until a lock is held.
     *
     * @param aggregateIdentifier the identifier of the aggregate to obtains a lock for.
     */
    public function obtainLock($aggregateIdentifier);

    /**
     * Release the lock held for an aggregate with the given <code>aggregateIdentifier</code>. The caller of this
     * method must ensure a valid lock was requested using {@link #obtainLock(Object)}. If no lock was successfully
     * obtained, the behavior of this method is undefined.
     *
     * @param aggregateIdentifier the identifier of the aggregate to release the lock for.
     */
    public function releaseLock($aggregateIdentifier);
}
