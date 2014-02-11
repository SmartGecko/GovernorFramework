<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\CommandHandling;

/**
 * Description of CommandCallback
 *
 * @author david
 */
final class CommandCallback
{

    private $successCallback;
    private $failureCallback;

    public function __construct(\Closure $successCallback,
        \Closure $failureCallback)
    {
        $this->successCallback = $successCallback;
        $this->failureCallback = $failureCallback;
    }

    /**
     * Invoked when command handling execution was successful.
     *
     * @param result The result of the command handling execution, if any.
     */
    public function onSuccess($result)
    {
        $cb = $this->successCallback;
        $cb($result);
    }

    /**
     * Invoked when command handling execution resulted in an error.
     *
     * @param cause The exception raised during command handling
     */
    public function onFailure($exception)
    {
        $cb = $this->failureCallback;
        $cb($exception);
    }

}
