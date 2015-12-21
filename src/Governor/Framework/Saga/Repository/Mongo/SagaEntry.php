<?php

/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * The software is based on the Axon Framework project which is
 * licensed under the Apache 2.0 license. For more information on the Axon Framework
 * see <http://www.axonframework.org/>.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.governor-framework.org/>.
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