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

    /**
     * @var RevisionResolverInterface 
     */
    private $revisionResolver;

    public function __construct(RevisionResolverInterface $revisionResolver)
    {
        $this->serializer = SerializerBuilder::create()
                ->configureHandlers(function(HandlerRegistry $registry) {
                    $registry->registerSubscribingHandler(new RhumsaaUuidHandler());
                })->build();

        $this->revisionResolver = $revisionResolver;
    }

    public function deserialize(SerializedObjectInterface $data)
    {        
        return $this->serializer->deserialize($data->getData(),
                $data->getContentType(), 'json');
    }

    public function serialize($object)
    {
        $result = $this->serializer->serialize($object, 'json');
        return new SimpleSerializedObject($result, $this->typeForClass($object));
    }

    public function typeForClass($object)
    {
        $type = get_class($object);
        return new SimpleSerializedType($type,
            $this->revisionResolver->revisionOf($type));
    }

}
