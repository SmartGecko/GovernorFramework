<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\CommandHandling;

use Doctrine\Common\Annotations\AnnotationReader;

/**
 * Description of AnnotationCommandTargetResolver
 *
 * @author david
 */
class AnnotationCommandTargetResolver implements CommandTargetResolverInterface
{

    /**
     * 
     * @param \Governor\Framework\CommandHandling\CommandMessageInterface $command
     * @return \Governor\Framework\CommandHandling\VersionedAggregateIdentifier
     * @throws \InvalidArgumentException
     */
    public function resolveTarget(CommandMessageInterface $command)
    {
        $reader = new AnnotationReader();
        $reflClass = new \ReflectionClass($command->getPayload());

        $id = $this->findIdentifier($command, $reader, $reflClass);
        $version = $this->findVersion($command, $reader, $reflClass);

        if (null === $id) {
            throw new \InvalidArgumentException(
            sprintf("Invalid command. It does not identify the target aggregate. " .
                    "Make sure at least one of the fields or methods in the [%s] class contains the " .
                    "@TargetAggregateIdentifier annotation and that it returns a non-null value.",
                    $command->getPayloadType()));
        }

        return new VersionedAggregateIdentifier($id, $version);
    }

    private function getAnnotatedTargetValue($annotationName,
            CommandMessageInterface $command, AnnotationReader $reader,
            \ReflectionClass $reflClass)
    {
        foreach ($reflClass->getProperties() as $property) {
            if (null !== $annot = $reader->getPropertyAnnotation($property,
                    $annotationName)) {
                $property->setAccessible(true);

                return $property->getValue($command->getPayload());
            }
        }

        foreach ($reflClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if (null !== $annot = $reader->getMethodAnnotation($method,
                    $annotationName)) {
                $method->setAccessible(true);

                return $method->invoke($command->getPayload());
            }
        }

        return null;
    }

    private function findIdentifier(CommandMessageInterface $command,
            AnnotationReader $reader, \ReflectionClass $reflClass)
    {
        return $this->getAnnotatedTargetValue('Governor\Framework\Annotations\TargetAggregateIdentifier',
                        $command, $reader, $reflClass);
    }

    private function findVersion(CommandMessageInterface $command,
            AnnotationReader $reader, \ReflectionClass $reflClass)
    {
        return $this->getAnnotatedTargetValue('Governor\Framework\Annotations\TargetAggregateVersion',
                        $command, $reader, $reflClass);
    }

}
