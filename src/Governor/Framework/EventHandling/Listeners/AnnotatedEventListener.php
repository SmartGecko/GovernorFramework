<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\EventHandling\Listeners;

use Governor\Framework\EventHandling\EventListenerInterface;
use Governor\Framework\Domain\EventMessageInterface;

/**
 * Description of AnnotatedEventHandler
 *
 * @author 255196
 */
class AnnotatedEventListener implements EventListenerInterface
{

    private $eventName;
    private $methodName;
    private $eventTarget;

    function __construct($eventName, $methodName, $eventTarget)
    {
        $this->eventName = $eventName;
        $this->methodName = $methodName;
        $this->eventTarget = $eventTarget;
    }

    public function handle(EventMessageInterface $event)
    {
        try {
            $this->verifyEventMessage($event);
            $reflMethod = new \ReflectionMethod($this->eventTarget,
                    $this->methodName);

            $reflMethod->invokeArgs($this->eventTarget,
                    array($event->getPayload()));
        } catch (\Exception $ex) {
            // ignore everything
        }
    }

    protected function verifyEventMessage(EventMessageInterface $message)
    {
        if ($message->getPayloadType() !== $this->eventName) {
            throw new \BadMethodCallException(sprintf("Invalid event in listener %s, expected %s but got %s",
                    get_class($this), $this->eventName,
                    $message->getPayloadType()));
        }
    }

}
