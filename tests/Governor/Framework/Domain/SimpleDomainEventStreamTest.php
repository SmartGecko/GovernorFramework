<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Domain;

use Rhumsaa\Uuid\Uuid;

/**
 * Description of SimpleDomainEventStreamTest
 *
 * @author david
 */
class SimpleDomainEventStreamTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers Governor\Framework\Domain\SimpleDomainEventStream::peek
     */
    public function testPeek()
    {
        $event1 = new GenericDomainEventMessage(Uuid::uuid1(), 0,
            "Mock contents", new MetaData());
        $event2 = new GenericDomainEventMessage(Uuid::uuid1(), 0,
            "Mock contents", new MetaData());
        $testSubject = new SimpleDomainEventStream(array($event1, $event2));
        $this->assertSame($event1, $testSubject->peek());
        $this->assertSame($event1, $testSubject->peek());
    }

    /**
     * @covers Governor\Framework\Domain\SimpleDomainEventStream::peek
     * @expectedException \OutOfBoundsException
     */
    public function testPeekEmptyStream()
    {
        $testSubject = new SimpleDomainEventStream();
        $this->assertFalse($testSubject->hasNext());

        $testSubject->peek();
    }

    /**
     * @covers Governor\Framework\Domain\SimpleDomainEventStream::hasNext
     * @covers Governor\Framework\Domain\SimpleDomainEventStream::next
     */
    public function testNextAndHasNext()
    {
        $event1 = new GenericDomainEventMessage(Uuid::uuid1(), 0,
            "Mock contents", new MetaData());
        $event2 = new GenericDomainEventMessage(Uuid::uuid1(), 0,
            "Mock contents", new MetaData());

        $testSubject = new SimpleDomainEventStream(array($event1, $event2));
        $this->assertTrue($testSubject->hasNext());
        $this->assertSame($event1, $testSubject->next());
        $this->assertTrue($testSubject->hasNext());
        $this->assertSame($event2, $testSubject->next());
        $this->assertFalse($testSubject->hasNext());
    }

    /**
     * @expectedException \OutOfBoundsException
     */
    public function testNextReadBeyondEnd()
    {
        $event1 = new GenericDomainEventMessage(Uuid::uuid1(), 0,
            "Mock contents", new MetaData());
        $testSubject = new SimpleDomainEventStream(array($event1));
        $testSubject->next();
        $testSubject->next();
    }

}
