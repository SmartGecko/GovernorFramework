<?php

namespace Governor\Framework\Serializer\Handlers;

use JMS\Serializer\Context;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\VisitorInterface;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use Governor\Framework\Domain\AggregateRootInterface;

class AggregateReferenceHandler implements SubscribingHandlerInterface
{

    public static function getSubscribingMethods()
    {
        $methods = array();
        $formats = array('json', 'xml', 'yml');

        foreach ($formats as $format) {
            $methods[] = array(
                'type' => 'Governor\Framework\Domain\AggregateRootInterface',
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format' => $format,
                'method' => 'serializeReference'
            );

            /*    $methods[] = array(
              'type' => 'Rhumsaa\Uuid\Uuid',
              'direction' => GraphNavigator::DIRECTION_DESERIALIZATION,
              'format' => $format,
              'method' => 'deserializeUuid'
              ); */
        }

        return $methods;
    }

    public function serializeReference(VisitorInterface $visitor,
        AggregateRootInterface $reference, array $type, Context $context)
    {
        return $visitor->visitString($reference->getIdentifier(), $type,
                $context);
    }

  /*  public function deserializeUuid(VisitorInterface $visitor, $data,
        array $type, Context $context)
    {
        return Uuid::fromString($data);
    }*/

}
