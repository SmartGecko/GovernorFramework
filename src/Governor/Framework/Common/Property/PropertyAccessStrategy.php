<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Common\Property;

/**
 * Description of PropertyAccessStrategyCollection
 *
 * @author david
 */
class PropertyAccessStrategy
{

    // !!! TODO consolidate and refactor 
    public static function getProperty($target, $propertyName)
    {
        $reflClass = new \ReflectionClass($target);

        foreach (array('get', 'is', 'has') as $prefix) {
            $methodName = sprintf('%s%s', $prefix, ucfirst($propertyName));

            foreach ($reflClass->getMethods() as $method) {
                if (0 === strcmp($method->getName(), $methodName)) {                     
                    return new ResolvedProperty($method);                    
                }
            }
        }
        
        return null;
    }

}
