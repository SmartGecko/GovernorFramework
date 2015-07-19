<?php
/**
 * This file is part of the SmartGecko(c) business platform.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Governor\Framework\EventStore\Mongo;

use Governor\Framework\Domain\DomainEventMessageInterface;
use Governor\Framework\Domain\GenericDomainEventMessage;
use Governor\Framework\Domain\MetaData;
use Governor\Framework\Serializer\MessageSerializer;
use Governor\Framework\Serializer\SerializerInterface;
use Governor\Framework\Serializer\SerializedDomainEventDataInterface;
use Governor\Framework\Serializer\SimpleSerializedObject;
use Governor\Framework\Serializer\SimpleSerializedType;

final class EventEntry implements SerializedDomainEventDataInterface
{

    /**
     * Property name in mongo for the Aggregate Identifier.
     */
    const AGGREGATE_IDENTIFIER_PROPERTY = 'aggregateIdentifier';

    /**
     * Property name in mongo for the Sequence Number.
     */
    const SEQUENCE_NUMBER_PROPERTY = 'scn';

    /**
     * Property name in mongo for the Aggregate's Type Identifier.
     */
    const AGGREGATE_TYPE_PROPERTY = 'type';

    /**
     * Property name in mongo for the Time Stamp.
     */
    const TIME_STAMP_PROPERTY = 'ts';

    const SERIALIZED_PAYLOAD_PROPERTY = 'serializedPayload';
    const PAYLOAD_TYPE_PROPERTY = 'payloadType';
    const PAYLOAD_REVISION_PROPERTY = 'payloadRevision';
    const META_DATA_PROPERTY = 'serializedMetaData';
    const EVENT_IDENTIFIER_PROPERTY = 'eventIdentifier';


    /**
     * @var string
     */
    private $aggregateIdentifier;

    /**
     * @var int
     */
    private $scn;

    /**
     * @var int
     */
    private $timeStamp;

    /**
     * @var string
     */
    private $aggregateType;

    /**
     * @var string
     */
    private $serializedPayload;

    /**
     * @var string
     */
    private $payloadType;

    /**
     * @var string
     */
    private $payloadRevision;

    /**
     * @var string
     */
    private $serializedMetaData;

    /**
     * @var string
     */
    private $eventIdentifier;

    /**
     * @param string $aggregateIdentifier
     * @param int $scn
     * @param int $timeStamp
     * @param string $aggregateType
     * @param string $serializedPayload
     * @param string $payloadType
     * @param string $payloadRevision
     * @param string $serializedMetaData
     * @param string $eventIdentifier
     */
    private function __construct(
        $aggregateIdentifier,
        $scn,
        $timeStamp,
        $aggregateType,
        $serializedPayload,
        $payloadType,
        $payloadRevision,
        $serializedMetaData,
        $eventIdentifier
    ) {
        $this->aggregateIdentifier = $aggregateIdentifier;
        $this->scn = $scn;
        $this->timeStamp = $timeStamp;
        $this->aggregateType = $aggregateType;
        $this->serializedPayload = $serializedPayload;
        $this->payloadType = $payloadType;
        $this->payloadRevision = $payloadRevision;
        $this->serializedMetaData = $serializedMetaData;
        $this->eventIdentifier = $eventIdentifier;
    }


    /**
     * Creates a new event entry to store in Mongo.
     *
     * @param string $aggregateType String containing the aggregate type of the event
     * @param DomainEventMessageInterface $event The actual DomainEvent to store
     * @param SerializerInterface $serializer Serializer to use for the event to store
     * @return EventEntry
     */
    public static function fromDomainEvent(
        $aggregateType,
        DomainEventMessageInterface $event,
        SerializerInterface $serializer
    ) {
        /**serializationTarget = String.class;
         * if (serializer.canSerializeTo(DBObject.class)) {
         * serializationTarget = DBObject.class;
         * }*/

        $messageSerializer = new MessageSerializer($serializer);

        $serializedPayloadObject = $messageSerializer->serializePayload($event);
        $serializedMetaDataObject = $messageSerializer->serializeMetaData($event);

        return new self(
            $event->getAggregateIdentifier(),
            $event->getScn(),
            $event->getTimestamp()->getTimestamp(),
            $aggregateType,
            $serializedPayloadObject->getData(),
            $serializedPayloadObject->getType()->getName(),
            $serializedPayloadObject->getType()->getRevision(),
            $serializedMetaDataObject->getData(),
            $event->getIdentifier()
        );
    }

    /**
     * Creates a new EventEntry based onm data provided by Mongo.
     *
     * @param array $dbObject Mongo object that contains data to represent an EventEntry
     * @return EventEntry
     */

    public static function fromDbObject(array $dbObject)
    {
        return new self(
            $dbObject[self::AGGREGATE_IDENTIFIER_PROPERTY],
            $dbObject[self::SEQUENCE_NUMBER_PROPERTY],
            $dbObject[self::TIME_STAMP_PROPERTY],
            $dbObject[self::AGGREGATE_TYPE_PROPERTY],
            $dbObject[self::SERIALIZED_PAYLOAD_PROPERTY],
            $dbObject[self::PAYLOAD_TYPE_PROPERTY],
            $dbObject[self::PAYLOAD_REVISION_PROPERTY],
            $dbObject[self::META_DATA_PROPERTY],
            $dbObject[self::EVENT_IDENTIFIER_PROPERTY]
        );
    }

    /**
     * Returns the actual DomainEvent from the EventEntry using the provided Serializer.
     *
     * @param string $actualAggregateIdentifier The actual aggregate identifier instance used to perform the lookup, or
     *                                  <code>null</code> if unknown
     * @param SerializerInterface $eventSerializer Serializer used to de-serialize the stored DomainEvent
     * @param mixed $upcasterChain             Set of upcasters to use when an event needs upcasting before
     *                                  de-serialization
     * @param bool $skipUnknownTypes whether to skip unknown event types
     * @return DomainEventMessageInterface[] The actual DomainEventMessage instances stored in this entry
     */

    public function  getDomainEvents(
        $actualAggregateIdentifier,
        SerializerInterface $eventSerializer,
        $upcasterChain,
        $skipUnknownTypes
    ) {
        // TODO upcasting

        $date = \DateTime::createFromFormat('U', $this->timeStamp);

        if (!$date) {
            throw new \RuntimeException('Incompatible date format');
        }

        return [
            new GenericDomainEventMessage(
                $this->aggregateIdentifier, $this->scn,
                $eventSerializer->deserialize(
                    new SimpleSerializedObject(
                        $this->serializedPayload,
                        new SimpleSerializedType($this->payloadType, $this->payloadRevision)
                    )
                ),
                $eventSerializer->deserialize(
                    new SimpleSerializedObject(
                        $this->serializedMetaData,
                        new SimpleSerializedType(MetaData::class)
                    )
                ),
                $this->eventIdentifier,
                $date
            )

        ];
        /*
        return upcastAndDeserialize(this, actualAggregateIdentifier, eventSerializer,
        upcasterChain, skipUnknownTypes);*/
    }

    /*   private Class<?> getRepresentationType() {
       Class<?> representationType = String.class;
       if (serializedPayload instanceof DBObject) {
       representationType = DBObject.class;
       }
       return representationType;
       }*/


    public function getEventIdentifier()
    {
        return $this->eventIdentifier;
    }


    public function getAggregateIdentifier()
    {
        return $this->aggregateIdentifier;
    }

    /**
     * getter for the sequence number of the event.
     *
     * @return int representing the sequence number of the event
     */
    public function getScn()
    {
        return $this->scn;
    }

    /**
     * @return \DateTime
     */
    public function getTimestamp()
    {
        return new \DateTime($this->timeStamp);
    }


    public function getMetaData()
    {
        return new SimpleSerializedObject($this->serializedMetaData, new SimpleSerializedType(MetaData::class));
    }


    public function getPayload()
    {
        return new SimpleSerializedObject(
            $this->serializedPayload,
            new SimpleSerializedType($this->payloadType, $this->payloadRevision)
        );
    }

    /**
     * Returns the current EventEntry as a mongo DBObject.
     *
     * @return array representing the EventEntry
     */
    public function asDBObject()
    {
        return [
            self::AGGREGATE_IDENTIFIER_PROPERTY => $this->aggregateIdentifier,
            self::SEQUENCE_NUMBER_PROPERTY => $this->scn,
            self::SERIALIZED_PAYLOAD_PROPERTY => $this->serializedPayload,
            self::TIME_STAMP_PROPERTY => $this->timeStamp,
            self::AGGREGATE_TYPE_PROPERTY => $this->aggregateType,
            self::PAYLOAD_TYPE_PROPERTY => $this->payloadType,
            self::PAYLOAD_REVISION_PROPERTY => $this->payloadRevision,
            self::META_DATA_PROPERTY => $this->serializedMetaData,
            self::EVENT_IDENTIFIER_PROPERTY => $this->eventIdentifier
        ];
    }

    /**
     * Returns the mongo DBObject used to query mongo for events for specified aggregate identifier and type.
     *
     * @param string $type The type of the aggregate to create the mongo DBObject for
     * @param string $aggregateIdentifier Identifier of the aggregate to obtain the mongo DBObject for
     * @param int $firstSequenceNumber number representing the first event to obtain
     * @return array Created DBObject based on the provided parameters to be used for a query
     */
    public static function forAggregate($type, $aggregateIdentifier, $firstSequenceNumber)
    {
        return [
            self::AGGREGATE_IDENTIFIER_PROPERTY => $aggregateIdentifier,
            self::SEQUENCE_NUMBER_PROPERTY => [
                '$gte' => $firstSequenceNumber
            ],
            self::AGGREGATE_TYPE_PROPERTY => $type
        ];
    }
}