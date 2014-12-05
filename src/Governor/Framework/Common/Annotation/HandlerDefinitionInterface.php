<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Common\Annotation;

/**
 *
 * @author 255196
 */
interface HandlerDefinitionInterface {

    /**
     * @return \ReflectionClass
     */
    public function getTarget();

    /**
     * @return string
     */
    public function getPayloadType();

    /**
     * @return \ReflectionMethod
     */
    public function getMethod();
}
