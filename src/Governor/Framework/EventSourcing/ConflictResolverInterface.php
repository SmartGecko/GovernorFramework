<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\EventSourcing;

/**
 * ConflictResolverInterface
 * 
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a> 
 */
interface ConflictResolverInterface
{

    /**
     * Checks the given list of <code>appliedChanges</code> and <code>committedChanges</code> for any conflicting
     * changes. If any such conflicts are detected, an instance of
     * {@link org.axonframework.repository.ConflictingModificationException} (or subtype) is thrown. If no conflicts
     * are detected, nothing happens.
     *
     * @param $appliedChanges   The list of the changes applied to the aggregate
     * @param $committedChanges The list of events that have been previously applied, but were unexpected by the command
     *                         handler
     * @throws \Governor\Framework\Repository\ConflictingModificationException
     *          if any conflicting changes are detected
     */
    public function resolveConflicts($appliedChanges, $committedChanges);
}
