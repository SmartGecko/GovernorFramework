<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\UnitOfWork;

use Governor\Framework\Domain\EventRegistrationCallbackInterface;
use Governor\Framework\Domain\DomainEventMessageInterface;

/**
 * Description of UoWEventRegistrationCallback
 *
 * @author 255196
 */
class UoWEventRegistrationCallback implements EventRegistrationCallbackInterface
{

    private $closure;

    public function __construct(\Closure $closure)
    {
        $this->closure = $closure;
    }

    public function onRegisteredEvent(DomainEventMessageInterface $event)
    {        
        $cb = $this->closure;
        return $cb($event);
    }

}
