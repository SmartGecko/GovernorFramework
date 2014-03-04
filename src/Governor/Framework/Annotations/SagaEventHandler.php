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
final class SagaEventHandler
{

    /**
     * The property in the event that will provide the value to find the Saga instance. Typically, this value is an
     * aggregate identifier of an aggregate that a specific saga monitors.
     */
    public $associationProperty;

    /**
     * The key in the AssociationValue to use. Optional. Should only be configured if that property is different than
     * the value given by {@link #associationProperty()}.
     */
    public $keyName = "";

    /**
     * The type of event this method handles. If specified, this handler will only be invoked for message that have a
     * payload assignable to the given payload type. If unspecified, the first parameter of the method defines the type
     * of supported event.
     */
    public $payloadType = null;

}
