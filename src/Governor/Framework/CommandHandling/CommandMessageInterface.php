<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\CommandHandling;

use Governor\Framework\Domain\MessageInterface;

/**
 *
 * @author david
 */
interface CommandMessageInterface extends MessageInterface
{

    /**
     * Returns the name of the command to execute. This is an indication of what should be done, using the payload as
     * parameter.
     *
     * @return the name of the command
     */
    public function getCommandName();

    /**
     * Returns a copy of this CommandMessage with the given <code>metaData</code>. The payload remains unchanged.
     * <p/>
     * While the implementation returned may be different than the implementation of <code>this</code>, implementations
     * must take special care in returning the same type of Message (e.g. EventMessage, DomainEventMessage) to prevent
     * errors further downstream.
     *
     * @param metaData The new MetaData for the Message
     * @return a copy of this message with the given MetaData
     */
    public function withMetaData(array $metadata = array());

    /**
     * Returns a copy of this CommandMessage with it MetaData merged with the given <code>metaData</code>. The payload
     * remains unchanged.
     *
     * @param metaData The MetaData to merge with
     * @return a copy of this message with the given MetaData
     */
    public function andMetaData(array $metadata = array());
}
