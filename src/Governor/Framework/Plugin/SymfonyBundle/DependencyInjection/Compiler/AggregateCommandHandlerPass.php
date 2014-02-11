<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Plugin\SymfonyBundle\DependencyInjection\Compiler;

use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Finder\Finder;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Description of AggregateCommandHandlerPass
 *
 * @author david
 */
class AggregateCommandHandlerPass implements CompilerPassInterface
{

    public function process(ContainerBuilder $container)
    {
        if (null === $locations = $container->getParameter('governor.aggregate_locations')) {
            throw new \InvalidArgumentException('aggregate_locations is not configured');
        }

        $reader = new AnnotationReader();
        $finder = new Finder();
        $finder->in($locations)->name('*.php')->files();

        foreach ($finder as $file) {
            $class = $this->getClassName($file);
            $reflClass = new \ReflectionClass($class);

            if (!$reflClass->implementsInterface('Governor\Framework\Domain\AggregateRootInterface')) {
                continue;
            }

            foreach ($reflClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                $annot = $reader->getMethodAnnotation($method,
                    'Governor\Framework\Annotations\CommandHandler');
                                
               if (null === $annot) {
                   continue;
               }
               
               print_r($method);
            }
        }
    }

    /**
     * Only supports one namespaced class per file
     *
     * @throws \RuntimeException if the class name cannot be extracted
     * @param string $filename
     * @return string the fully qualified class name
     */
    private function getClassName($filename)
    {
        $src = file_get_contents($filename);

        if (!preg_match('/\bnamespace\s+([^;]+);/s', $src, $match)) {
            throw new \RuntimeException(sprintf('Namespace could not be determined for file "%s".',
                $filename));
        }
        $namespace = $match[1];

        if (!preg_match('/[\bclass|\binterface]\s+([^\s]+)\s+(?:extends|implements|{)/s',
                $src, $match)) {
            throw new \RuntimeException(sprintf('Could not extract class name from file "%s".',
                $filename));
        }

        return $namespace . '\\' . $match[1];
    }

}
