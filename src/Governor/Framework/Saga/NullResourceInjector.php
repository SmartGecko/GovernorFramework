<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Saga;

/**
 * Description of NullResourceInjector
 *
 * @author david
 */
class NullResourceInjector implements ResourceInjectorInterface
{
    public function injectResources(SagaInterface $saga)
    {
        
    }

}
