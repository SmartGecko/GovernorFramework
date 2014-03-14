<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\UnitOfWork;

use Governor\Framework\Domain\EventMessageInterface;

/**
 * Description of UnitOfWorkListenerCollection
 *
 * @author david
 */
class UnitOfWorkListenerCollection implements UnitOfWorkListenerInterface
{

    private $listeners = array();

    public function afterCommit(UnitOfWorkInterface $unitOfWork)
    {
        foreach (array_reverse($this->listeners) as $listener) {
            $listener->afterCommit($unitOfWork);
        }
    }

    public function onCleanup(UnitOfWorkInterface $unitOfWork)
    {
        foreach (array_reverse($this->listeners) as $listener) {
            try {
                $listener->onCleanup($unitOfWork);
            } catch (\Exception $ex) {
                
            }
        }
    }

    public function onEventRegistered(UnitOfWorkInterface $unitOfWork,
        EventMessageInterface $event)
    {        
        $newEvent = $event;
        foreach ($this->listeners as $listener) {        
            $newEvent = $listener->onEventRegistered($unitOfWork, $newEvent);
        }
                
        return $newEvent;
    }

    public function onPrepareCommit(UnitOfWorkInterface $unitOfWork,
        array $aggregateRoots, array $events)
    {
        foreach ($this->listeners as $listener) {
            $listener->onPrepareCommit($unitOfWork, $aggregateRoots, $events);
        }
    }

    public function onPrepareTransactionCommit(UnitOfWorkInterface $unitOfWork,
        $transaction)
    {
        foreach ($this->listeners as $listener) {
            $listener->onPrepareTransactionCommit($unitOfWork, $transaction);
        }
    }

    public function onRollback(UnitOfWorkInterface $unitOfWork,
        \Exception $failureCause = null)
    {
        foreach (array_reverse($this->listeners) as $listener) {
            $listener->onRollback($unitOfWork, $failureCause);
        }
    }

    public function add(UnitOfWorkListenerInterface $listener)
    {
        $this->listeners[] = $listener;
    }

}
