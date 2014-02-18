<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Serializer;

use JMS\Serializer\Handler\HandlerRegistry;
use JMS\Serializer\SerializerBuilder;
use Governor\Framework\Serializer\Handlers\RhumsaaUuidHandler;

class JMSSerializer implements SerializerInterface
{

    /**
     * @var JMS\Serializer\Serializer
     */
    private $serializer;

    public function __construct()
    {
        $this->serializer = SerializerBuilder::create()
                        ->configureHandlers(function(HandlerRegistry $registry) {
                            $registry->registerSubscribingHandler(new RhumsaaUuidHandler());
                        })->build();
    }

    public function deserialize($data, $type)
    {
        return $this->serializer->deserialize($data, $type, 'json');
    }

    public function serialize($object)
    {
        return $this->serializer->serialize($object, 'json');
    }

}
