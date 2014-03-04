<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Annotations;

/**
 * @Annotation
 * @Target("METHOD")
 */
final class StartSaga
{

    /**
     * Indicates whether or not to force creation of a Saga, even if one already exists. If <code>true</code>, a new
     * Saga is always created when an event assignable to the annotated method is handled. If <code>false</code>, a new
     * saga is only created if no Saga's exist that can handle the incoming event.
     * <p/>
     */
    public $forceNew = false;

}
