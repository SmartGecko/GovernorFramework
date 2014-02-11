<?php

namespace Governor\Framework\Eventing;

use Governor\Framework\DefaultDomainEvent;

class EventExecutionFailed extends DefaultDomainEvent
{
    public $service;
    public $exception;
    public $event;
}

