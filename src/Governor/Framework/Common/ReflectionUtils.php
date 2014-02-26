<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Common;

/**
 * Description of ReflectionUtils
 *
 * @author david
 */
class ReflectionUtils
{

    /**
     * Returns a list of all properties declared in either the $class or any of its parents.
     * 
     * @param \ReflectionClass $class
     * @return array<ReflectionProperty>
     */
    public static function getProperties(\ReflectionClass $class)
    {
        $current = $class;
        $result = array();

        do {
            $result = array_merge($result, $current->getProperties());
            $current = $current->getParentClass();
        } while ($current);

        return $result;
    }

    /**
     * Returns a list of public methods declared in either the $class or any of its parents.
     * 
     * @param \ReflectionClass $class
     * @return array<ReflectionMethod>
     */
    public static function getMethods(\ReflectionClass $class)
    {
        return
            $class->getMethods(\ReflectionMethod::IS_PUBLIC);
    }
    
    /**
     * Returns a reflection class for the object. If the object is an Orm Proxy it returns the parent class.
     * @param type $class
     */
    public static function getClass ($object)
    {
        $reflClass = new \ReflectionClass($object);
        
        if ($reflClass->implementsInterface('Doctrine\ORM\Proxy\Proxy')) {
            return $reflClass->getParentClass();            
        }
        
        return $reflClass;
    }

}
