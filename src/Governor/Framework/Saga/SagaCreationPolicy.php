<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Saga;

/**
 * Description of SagaCreationPolicy
 *
 * @author david
 */
final class SagaCreationPolicy
{

    /**
     * Never create a new Saga instance, even if none exists.
     */
    const NONE = 0;

    /**
     * Only create a new Saga instance if none can be found.
     */
    const IF_NONE_FOUND = 1;

    /**
     * Always create a new Saga, even if one already exists.
     */
    const ALWAYS = 2;

}
