<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Common\Property;

/**
 * Description of SimpleProperty
 *
 * @author david
 */
class ResolvedProperty implements PropertyInterface
{

    private $method;

    function __construct(\ReflectionMethod $method)
    {
        $this->method = $method;
    }

    public function getValue($target)
    {
        $this->method->setAccessible(true);
        return $this->method->invoke($target);
    }

}
