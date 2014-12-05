<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Common\Annotation;

/**
 * Description of AbstractAnnotatedHandlerDefinition
 *
 * @author 255196
 */
class AnnotatedHandlerDefinition implements HandlerDefinitionInterface
{   
    
    /**     
     * @var \ReflectionClass
     */
    private $target;
    /**
     *
     * @var \ReflectionMethod
     */
    private $method;
    
    /**     
     * @var string
     */
    private $payloadType;
    
    function __construct(\ReflectionClass $target, \ReflectionMethod $method, 
            $payloadType) 
    {
        $this->target = $target;
        $this->method = $method;
        $this->payloadType = $payloadType;
    }

    public function getMethod() 
    {
        return $this->method;
    }

    public function getPayloadType() 
    {
        return $this->payloadType;
    }

    public function getTarget() 
    {
        return $this->target;
    }

}
