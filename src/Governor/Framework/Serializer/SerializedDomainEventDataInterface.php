<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Serializer;

/**
 * Interface describing the properties of serialized Domain Event Messages. Event Store implementations should have
 * their storage entries implement this interface.
 */
interface SerializedDomainEventDataInterface
{

    /**
     * Returns the identifier of the serialized event.
     *
     * @return string the identifier of the serialized event
     */
    public function getEventIdentifier();

    /**
     * Returns the Identifier of the Aggregate to which the Event was applied.
     *
     * @return string the Identifier of the Aggregate to which the Event was applied
     */
    public function getAggregateIdentifier();

    /**
     * Returns the sequence number of the event in the aggregate.
     *
     * @return integer the sequence number of the event in the aggregate
     */
    public function getScn();

    /**
     * Returns the timestamp at which the event was first created.
     *
     * @return \DateTime the timestamp at which the event was first created
     */
    public function getTimestamp();

    /**
     * Returns the serialized data of the MetaData of the serialized Event.
     *
     * @return SerializedObjectInterface the serialized data of the MetaData of the serialized Event
     */
    public function getMetaData();

    /**
     * Returns the serialized data of the Event Message's payload.
     *
     * @return SerializedObjectInterface the serialized data of the Event Message's payload
     */
    public function getPayload();
}
