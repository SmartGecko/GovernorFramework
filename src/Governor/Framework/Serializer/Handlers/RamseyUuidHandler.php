<?php

namespace Governor\Framework\Serializer\Handlers;

use JMS\Serializer\Context;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\VisitorInterface;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use Ramsey\Uuid\Uuid;

class RamseyUuidHandler implements SubscribingHandlerInterface
{

    public static function getSubscribingMethods()
    {
        $methods = array();
        $formats = array('json', 'xml', 'yml');

        foreach ($formats as $format) {
            $methods[] = array(
                'type' => 'Ramsey\Uuid\Uuid',
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format' => $format,
                'method' => 'serializeUuid'
            );

            $methods[] = array(
                'type' => 'Ramsey\Uuid\Uuid',
                'direction' => GraphNavigator::DIRECTION_DESERIALIZATION,
                'format' => $format,
                'method' => 'deserializeUuid'
            );
        }

        return $methods;
    }

    public function serializeUuid(VisitorInterface $visitor, Uuid $uuid,
            array $type, Context $context)
    {
        return $visitor->visitString($uuid->toString(), $type, $context);
    }

    public function deserializeUuid(VisitorInterface $visitor, $data,
            array $type, Context $context)
    {
        return Uuid::fromString($data);
    }

}
