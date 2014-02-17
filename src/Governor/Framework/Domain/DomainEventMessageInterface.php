<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Domain;

/**
 *
 * @author david
 */
interface DomainEventMessageInterface extends EventMessageInterface
{

    /**
     * Returns the aggregate identifier.
     * 
     * @return mixed
     */
    public function getAggregateIdentifier();

    /**
     * Returns the sequence number that allows DomainEvents originating from the same Aggregate to be placed in the
     * order of generation.
     *
     * @return integer The sequence number of this Event
     */
    public function getScn();
}
