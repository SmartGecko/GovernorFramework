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
interface EventMessageInterface extends MessageInterface
{
    
    /**
     * Returns the timestamp of this event.
     * 
     * @return \DateTime
     */
    public function getTimestamp();
}
