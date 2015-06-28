<?php
/**
 * This file is part of the SmartGecko(c) business platform.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Governor\Framework\Saga\Repository\Mongo;

use Governor\Framework\Saga\SagaInterface;
use Governor\Framework\Serializer\SerializerInterface;
use Governor\Framework\Serializer\SimpleSerializedObject;
use Governor\Framework\Serializer\SimpleSerializedType;
use Governor\Framework\Saga\AssociationValue;

/**
 * Java representation of sagas stored in a mongo instance
 *
 * @author Jettro Coenradie
 * @since 2.0
 */
class SagaEntry
{

    const SAGA_IDENTIFIER = "sagaIdentifier";
    const SERIALIZED_SAGA = "serializedSaga";
    const SAGA_TYPE = "sagaType";
    const ASSOCIATIONS = "associations";
    const ASSOCIATION_KEY = "key";
    const ASSOCIATION_VALUE = "value";

    /**
     * @var string
     */
    private $sagaId;
    /**
     * @var string
     */
    private $sagaType;

    /**
     * @var mixed
     */
    private $serializedSaga;

    private $saga;
    /**
     * @var AssociationValue[]
     */
    private $associationValues = [];

    /**
     * Constructs a new SagaEntry for the given <code>saga</code>. The given saga must be serializable. The provided
     * saga is not modified by this operation.
     *
     * @param SagaInterface $saga The saga to store
     * @param SerializerInterface $serializer The serialization mechanism to convert the Saga to a byte stream
     */
    public function  __construct(SagaInterface $saga, SerializerInterface $serializer)
    {
        $this->sagaId = $saga->getSagaIdentifier();
        $serialized = $serializer->serialize($saga);
        $this->serializedSaga = $serialized->getData();
        $this->sagaType = get_class($saga);
        $this->saga = $saga;
        $this->associationValues = $saga->getAssociationValues()->asArray();
    }

    /**
     * Returns the Saga instance stored in this entry.
     *
     * @param SerializerInterface $serializer The serializer to decode the Saga
     * @return SagaInterface the Saga instance stored in this entry
     */
    public function getSaga(SerializerInterface $serializer)
    {
        if (null !== $this->saga) {
            return $this->saga;
        }

        return $serializer->deserialize(
            new SimpleSerializedObject($this->serializedSaga, new SimpleSerializedType($this->sagaType))
        );
    }

    /**
     * Returns the Mongo Document representing the Saga provided in this entry.
     *
     * @return array the Mongo Document representing the Saga provided in this entry
     */
    public function asDBObject()
    {
        return [
            self::SAGA_TYPE => $this->sagaType,
            self::SAGA_IDENTIFIER => $this->sagaId,
            self::SERIALIZED_SAGA => $this->serializedSaga,
            self::ASSOCIATIONS => self::toDBList($this->associationValues)
        ];
    }

    /**
     * @param array $dbSaga
     * @return AssociationValue[]
     */
    private function  toAssociationArray(array $dbSaga)
    {
        $values = [];

        if (empty($dbSaga['associations'])) {
            return $values;
        }

        foreach ($dbSaga['associations'] as $association) {
            $values[] = new AssociationValue(
                $association[self::ASSOCIATION_KEY], $association[self::ASSOCIATION_VALUE]
            );
        }

        return $values;
    }

    /**
     * @param AssociationValue[] $associationValues
     * @return array
     */
    private static function toDBList(array $associationValues)
    {
        $list = [];

        foreach ($associationValues as $associationValue) {
            $list[] = [
                self::ASSOCIATION_KEY => $associationValue->getPropertyKey(),
                self::ASSOCIATION_VALUE => $associationValue->getPropertyValue()
            ];
        }

        return $list;
    }

    /**
     * Returns the Mongo Query to find a Saga based on its identifier.
     *
     * @param string $identifier The identifier of the saga to find
     * @return array the Query (as DBObject) to find a Saga in a Mongo Database
     */
    public static function queryByIdentifier($identifier)
    {
        return [
            self::SAGA_IDENTIFIER => $identifier
        ];

    }
}