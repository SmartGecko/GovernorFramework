<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Serializer;

/**
 * Description of AbstractSerializer
 *
 * @author david
 */
abstract class AbstractSerializer implements SerializerInterface
{

    /**
     * @var RevisionResolverInterface 
     */
    private $revisionResolver;

    function __construct(RevisionResolverInterface $revisionResolver)
    {
        $this->revisionResolver = $revisionResolver;
    }

    public function typeForClass($object)
    {
        $type = get_class($object);
        return new SimpleSerializedType($type,
            $this->revisionResolver->revisionOf($type));
    }

}
