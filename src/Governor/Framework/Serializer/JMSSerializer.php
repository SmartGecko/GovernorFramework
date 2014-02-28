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
use Governor\Framework\Serializer\Handlers\AggregateReferenceHandler;

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
                ->addDefaultHandlers()                
                ->configureHandlers(function(HandlerRegistry $registry) {
                    $registry->registerSubscribingHandler(new RhumsaaUuidHandler());
                    $registry->registerSubscribingHandler(new AggregateReferenceHandler());
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
        try {
        $result = $this->serializer->serialize($object, 'json');
        }catch (\Exception $ex) {
            echo $ex->getMessage();
        }
        return new SimpleSerializedObject($result, $this->typeForClass($object));
    }

    public function typeForClass($object)
    {
        $type = get_class($object);
        return new SimpleSerializedType($type,
            $this->revisionResolver->revisionOf($type));
    }

}
