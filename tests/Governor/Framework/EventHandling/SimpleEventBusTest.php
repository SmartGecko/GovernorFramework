<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\EventHandling;

use Governor\Framework\Domain\GenericEventMessage;

/**
 * Description of SimpleEventBusTest
 *
 * @author 255196
 */
class SimpleEventBusTest extends \PHPUnit_Framework_TestCase
{

    private $listener1;
    private $listener2;
    private $testSubject;
    private $locator;
    private $listener3;

    public function setUp()
    {
        $this->listener1 = $this->getMock('Governor\Framework\EventHandling\EventListenerInterface',
                array('handle'));
        $this->listener2 = $this->getMock('Governor\Framework\EventHandling\EventListenerInterface',
                array('handle'));
        $this->listener3 = $this->getMock('Governor\Framework\EventHandling\EventListenerInterface',
                array('handle'));

        $this->locator = new InMemoryEventListenerLocator();
        $this->testSubject = new SimpleEventBus($this->locator);
    }

    public function testEventIsDispatchedToSubscribedListeners()
    {
        $this->assertNotSame($this->listener1, $this->listener2);
        $this->assertNotSame($this->listener1, $this->listener3);

        $this->listener1->expects($this->exactly(2))
                ->method('handle');

        $this->listener2->expects($this->exactly(2))
                ->method('handle');

        $this->listener3->expects($this->exactly(2))
                ->method('handle');

        $this->testSubject->publish($this->newEvent());
        $this->locator->subscribe('Governor\Framework\EventHandling\StubEventMessage',
                $this->listener1);

        // subscribing twice should not make a difference
        $this->locator->subscribe('Governor\Framework\EventHandling\StubEventMessage',
                $this->listener1);
        $this->testSubject->publish($this->newEvent());
        $this->locator->subscribe('Governor\Framework\EventHandling\StubEventMessage',
                $this->listener2);
        $this->locator->subscribe('Governor\Framework\EventHandling\StubEventMessage',
                $this->listener3);
        $this->testSubject->publish($this->newEvent());
        $this->locator->unsubscribe('Governor\Framework\EventHandling\StubEventMessage',
                $this->listener1);
        $this->testSubject->publish($this->newEvent());
        $this->locator->unsubscribe('Governor\Framework\EventHandling\StubEventMessage',
                $this->listener2);
        $this->locator->unsubscribe('Governor\Framework\EventHandling\StubEventMessage',
                $this->listener3);
        // unsubscribe a non-subscribed listener should not fail
        $this->locator->unsubscribe('Governor\Framework\EventHandling\StubEventMessage',
                $this->listener3);
        $this->testSubject->publish($this->newEvent());
    }

    private function newEvent()
    {
        return array(new GenericEventMessage(new StubEventMessage()));
    }

}

class StubEventMessage
{
    
}
