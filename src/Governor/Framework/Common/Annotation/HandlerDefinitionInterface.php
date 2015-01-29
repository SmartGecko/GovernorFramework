<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Common\Annotation;

/**
 * Interface defining a handler method.
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a> 
 */
interface HandlerDefinitionInterface
{

    /**
     * The target class.
     *
     * @return \ReflectionClass
     */
    public function getTarget();

    /**
     * The payload type of this handler.
     *
     * @return string
     */
    public function getPayloadType();

    /**
     *
     *
     * @return \ReflectionMethod
     */
    public function getMethod();

    /**
     * Array of annotations on the handler method.
     *
     * @return array
     */
    public function getMethodAnnotations();
}
