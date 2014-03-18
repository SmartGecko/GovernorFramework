<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Saga\Repository\Orm;

use Governor\Framework\Saga\SagaInterface;
use Governor\Framework\Serializer\SerializerInterface;
use Governor\Framework\Serializer\SimpleSerializedObject;
use Governor\Framework\Serializer\SimpleSerializedType;

/**
 * Description of SagaEntry
 *
 * @author david
 */
class SagaEntry
{

    private $sagaId;
    private $sagaType;
    private $revision;
    private $serializedSaga;
    private $saga;

    /**
     * Constructs a new SagaEntry for the given <code>saga</code>. The given saga must be serializable. The provided
     * saga is not modified by this operation.
     *
     * @param saga       The saga to store
     * @param serializer The serialization mechanism to convert the Saga to a byte stream
     */
    public function __construct(SagaInterface $saga,
            SerializerInterface $serializer)
    {
        $this->sagaId = $saga->getSagaIdentifier();
        $serialized = $serializer->serialize($saga);
        $this->serializedSaga = $serialized->getData();
        $this->sagaType = $serialized->getType()->getName();
        $this->revision = $serialized->getType()->getRevision();
        $this->saga = $saga;
    }

    /**
     * Returns the Saga instance stored in this entry.
     *
     * @param serializer The serializer to decode the Saga
     * @return the Saga instance stored in this entry
     */
    public function getSaga(SerializerInterface $serializer)
    {
        if (null !== $this->saga) {
            return $this->saga;
        }
        return $serializer->deserialize(new SimpleSerializedObject($this->serializedSaga,
                        new SimpleSerializedType($this->sagaType,
                        $this->revision)));
    }

    /**
     * Returns the serialized form of the Saga.
     *
     * @return the serialized form of the Saga
     */
    public function getSerializedSaga()
    {
        return $this->serializedSaga;
    }

    /**
     * Returns the identifier of the saga contained in this entry
     *
     * @return the identifier of the saga contained in this entry
     */
    public function getSagaId()
    {
        return $this->sagaId;
    }

    /**
     * Returns the revision of the serialized saga
     *
     * @return the revision of the serialized saga
     */
    public function getRevision()
    {
        return $this->revision;
    }

    /**
     * Returns the type identifier of the serialized saga
     * @return the type identifier of the serialized saga
     */
    public function getSagaType()
    {
        return $this->sagaType;
    }

}
