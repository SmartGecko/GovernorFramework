<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Domain;

/**
 * Description of MessageInterface
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
interface MessageInterface
{

    /**
     * Returns the message identifier
     * 
     * @return string
     */
    public function getIdentifier();

    /**
     * Returns the message metadata
     * 
     * @return MetaData
     */
    public function getMetaData();

    /**
     * Message payload
     * 
     * @return mixed
     */
    public function getPayload();

    /**
     * Returns the message payload class
     * 
     * @return string
     */
    public function getPayloadType();

    /**
     * Returns a copy of this Message with the given <code>metaData</code>. The payload remains unchanged.
     * <p/>
     * While the implementation returned may be different than the implementation of <code>this</code>, implementations
     * must take special care in returning the same type of Message (e.g. EventMessage, DomainEventMessage) to prevent
     * errors further downstream.
     *
     * @param array $metaData The new MetaData for the Message
     * @return MessageInterface a copy of this message with the given MetaData
     */
    public function withMetaData(array $metaData = array());

    /**
     * Returns a copy of this Message with it MetaData merged with the given <code>metaData</code>. The payload
     * remains unchanged.
     *
     * @param array $metaData The MetaData to merge with
     * @return MessageInterface a copy of this message with the given MetaData
     */
    public function andMetaData(array $metaData = array());
}
