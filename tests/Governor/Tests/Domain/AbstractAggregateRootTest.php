<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Tests\Domain;

use Ramsey\Uuid\Uuid;
use Governor\Framework\Domain\AbstractAggregateRoot;

/**
 * Description of AbstractAggregateRootTest
 *
 * @author david
 */
class StubDomainEvent
{
    
}

class AggregateRoot extends AbstractAggregateRoot
{
    private $id;
    
    public function __construct()
    {
        $this->id = Uuid::uuid1()->toString();
    }

    public function doSomething()
    {
        $this->registerEvent(new StubDomainEvent());
    }

    public function getIdentifier()
    {
        return $this->id;
    }

}

class AbstractAggregateRootTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->object = new AggregateRoot();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
        
    }

    /**
     * @covers Governor\Framework\Domain\AbstractAggregateRoot::registerEvent
     */
    public function testRegisterEvent()
    {
        $this->assertEquals(0, $this->object->getUncommittedEventCount());
        $this->object->doSomething();
        $this->assertEquals(1, $this->object->getUncommittedEventCount());
    }

    public function testReadEventStreamDuringEventCommit()
    {
        $this->object->doSomething();
        $this->object->doSomething();

        $uncomittedEvents = $this->object->getUncommittedEvents();
        $uncomittedEvents->next();
        $this->object->commitEvents();
        $this->assertTrue($uncomittedEvents->hasNext());
        $this->assertNotNull($uncomittedEvents->next());
        $this->assertFalse($uncomittedEvents->hasNext());
    }

}
