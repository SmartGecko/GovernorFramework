<?php

namespace Governor\Framework\EventHandling;

interface EventHandlerLocator
{
    public function getHandlersFor(EventName $eventName);
}
