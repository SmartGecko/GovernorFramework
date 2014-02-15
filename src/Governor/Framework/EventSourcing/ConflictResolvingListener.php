<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\EventSourcing;

use Governor\Framework\UnitOfWork\UnitOfWorkListenerAdapter;
use Governor\Framework\UnitOfWork\UnitOfWorkInterface;

/**
 * Description of ConflictResolverListener
 *
 * @author david
 */
class ConflictResolvingListener extends UnitOfWorkListenerAdapter
{

    private $aggregate;
    private $unseenEvents;
    private $conflictResolver;

    public function __construct($aggregate, array $unseenEvents,
        ConflictResolverInterface $conflictResolver = null)
    {
        $this->aggregate = $aggregate;
        $this->unseenEvents = $unseenEvents;
        $this->conflictResolver = $conflictResolver;
    }

    public function onPrepareCommit(UnitOfWorkInterface $unitOfWork,
        array $aggregateRoots, array $events)
    {
        if ($this->hasPotentialConflicts()) {            
            $this->conflictResolver->resolveConflicts($this->aggregate->getUncommittedEvents(),
                $this->unseenEvents);
        }
    }

    private function hasPotentialConflicts()
    {      
        return $this->aggregate->getUncommittedEventCount() > 0 
            && null !== $this->aggregate->getVersion() 
            && !empty($this->unseenEvents);
    }

}
