<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Tests\Domain;

use Ramsey\Uuid\Uuid;
use Governor\Framework\Domain\EventRegistrationCallbackInterface;
use Governor\Framework\Domain\EventContainer;
use Governor\Framework\Domain\MetaData;
use Governor\Framework\Domain\DomainEventMessageInterface;
/**
 * Description of EventContainerTest
 *
 * @author david
 */
class EventContainerTest extends \PHPUnit_Framework_TestCase
{

    public function testAddEventIdAndSequenceNumberInitialized()
    {
        $id = Uuid::uuid1();

        $eventContainer = new EventContainer($id);
        $this->assertEquals($id, $eventContainer->getAggregateIdentifier());
        $eventContainer->initializeSequenceNumber(11);

        $this->assertEquals(0, $eventContainer->size());
        $this->assertFalse($eventContainer->getEventStream()->hasNext());

        $eventContainer->addEvent(MetaData::emptyInstance(), new Event());

        $this->assertEquals(1, $eventContainer->size());

        $domainEvent = $eventContainer->getEventList()[0];
        $this->assertEquals(12, $domainEvent->getScn());
        $this->assertEquals($id, $domainEvent->getAggregateIdentifier());
        $this->assertTrue($eventContainer->getEventStream()->hasNext());

        $eventContainer->commit();

        $this->assertEquals(0, $eventContainer->size());
    }

    public function testRegisterCallbackInvokedWithAllRegisteredEvents()
    {
        $container = new EventContainer(Uuid::uuid1()->toString());
        $container->addEvent(MetaData::emptyInstance(), new Event());

        $this->assertFalse(current($container->getEventList())->getMetaData()->has("key"));

        $container->addEventRegistrationCallback(new TestEventRegistrationCallback());

        $this->assertEquals("value",
            current($container->getEventList())->getMetadata()->get("key"));
    }

}

class TestEventRegistrationCallback implements EventRegistrationCallbackInterface
{

    public function onRegisteredEvent(DomainEventMessageInterface $event)
    {
        return $event->withMetaData(array("key" => "value"));
    }

}

class Event
{

    public $property;

}
