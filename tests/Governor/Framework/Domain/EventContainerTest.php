<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Domain;

use Rhumsaa\Uuid\Uuid;

/**
 * Description of EventContainerTest
 *
 * @author david
 */
class EventContainerTest extends \PHPUnit_Framework_TestCase {

    
    /**
     * @covers Governor\Framework\Domain\EventContainer::getAggregateId
     * @covers Governor\Framework\Domain\EventContainer::size
     * @covers Governor\Framework\Domain\EventContainer::getEventStream
     * @covers Governor\Framework\Domain\EventContainer::addEvent
     * @covers Governor\Framework\Domain\EventContainer::getEventList
     * @covers Governor\Framework\Domain\EventContainer::commit
     */
    public function testAddEventIdAndSequenceNumberInitialized() {
        $id = Uuid::uuid1();

        $eventContainer = new EventContainer($id);
        $this->assertEquals($id, $eventContainer->getAggregateId());
        $eventContainer->initializeSequenceNumber(11);
        
        $this->assertEquals(0, $eventContainer->size());
        $this->assertFalse($eventContainer->getEventStream()->hasNext());
        
        $eventContainer->addEvent(new MetaData(), new Event());
        
        $this->assertEquals(1, $eventContainer->size());
        
        $domainEvent = $eventContainer->getEventList()[0];
        $this->assertEquals(12, $domainEvent->getScn());        
        $this->assertEquals($id, $domainEvent->getAggregateId());
        $this->assertTrue($eventContainer->getEventStream()->hasNext());
        
        $eventContainer->commit();
        
        $this->assertEquals(0, $eventContainer->size());       
    }

    /**
     * 
     *
    public function testRegisterCallbackInvokedWithAllRegisteredEvents() {
        EventContainer container = new EventContainer(UUID.randomUUID().toString());
        container.addEvent(metaData, "payload");

        assertFalse(container.getEventList().get(0).getMetaData().containsKey("key"));

        container.addEventRegistrationCallback(new EventRegistrationCallback() {
            @Override
            public <T> DomainEventMessage<T> onRegisteredEvent(DomainEventMessage<T> event) {
                return event.withMetaData(singletonMap("key", "value"));
            }
        });

        DomainEventMessage firstEvent = container.getEventList().get(0);
        assertEquals("value", firstEvent.getMetaData().get("key"));
    }*/
}


class Event {
    public $property;
}