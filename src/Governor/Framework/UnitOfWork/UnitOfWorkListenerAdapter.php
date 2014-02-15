<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\UnitOfWork;

use Governor\Framework\Domain\EventMessageInterface;

/**
 * Description of UnitOfWorkListenerAdapter
 *
 * @author david
 */
abstract class UnitOfWorkListenerAdapter implements UnitOfWorkListenerInterface
{

    public function afterCommit(UnitOfWorkInterface $unitOfWork)
    {
        
    }

    public function onCleanup(UnitOfWorkInterface $unitOfWork)
    {
        
    }

    public function onEventRegistered(UnitOfWorkInterface $unitOfWork,
        EventMessageInterface $event)
    {
        return $event;
    }

    public function onPrepareCommit(UnitOfWorkInterface $unitOfWork,
        array $aggregateRoots, array $events)
    {
        
    }

    public function onPrepareTransactionCommit(UnitOfWorkInterface $unitOfWork,
        $transaction)
    {
        
    }

    public function onRollback(UnitOfWorkInterface $unitOfWork,
        \Exception $failureCause = null)
    {
        
    }

}
