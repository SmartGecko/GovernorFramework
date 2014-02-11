<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Domain;

use Rhumsaa\Uuid\Uuid;
use Governor\Framework\Domain\GenericDomainEventMessage;
use Governor\Framework\Domain\MetaData;

/**
 * Description of GenericDomainEventMessageTest
 *
 * @author david
 */
class GenericDomainEventMessageTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers Governor\Framework\Domain\GenericDomainEventMessage::__construct
     */
    public function testConstructor()
    {
        $payload = new Payload();
        $seqNo = 0;
        $id = Uuid::uuid1();
        $message1 = new GenericDomainEventMessage($id, $seqNo, $payload,
            MetaData::emptyInstance());
        $metaData = new Metadata(array('key' => 'value'));
        $message2 = new GenericDomainEventMessage($id, $seqNo, $payload,
            $metaData);


        $this->assertSame($id, $message1->getAggregateId());
        $this->assertEquals($seqNo, $message1->getScn());
        $this->assertSame(MetaData::emptyInstance(), $message1->getMetaData());
        $this->assertEquals(get_class($payload),
            get_class($message1->getPayload()));
        $this->assertEquals(get_class($payload), $message1->getPayloadType());

        $this->assertSame($id, $message2->getAggregateId());
        $this->assertEquals($seqNo, $message2->getScn());
        $this->assertSame($metaData, $message2->getMetaData());
        $this->assertEquals(get_class($payload),
            get_class($message2->getPayload()));
        $this->assertEquals(get_class($payload), $message2->getPayloadType());

        $this->assertNotEquals($message1->getId(), $message2->getId());
    }

    /**
     * @covers Governor\Framework\Domain\GenericDomainEventMessage::withMetaData
     */
    public function testWithMetaData()
    {
        $payload = new Payload();
        $seqNo = 0;
        $id = Uuid::uuid1();
        $metaData = new MetaData(array('key' => 'value'));

        $message = new GenericDomainEventMessage($id, $seqNo, $payload,
            $metaData);
        $message1 = $message->withMetaData();
        $message2 = $message->withMetaData(array('key' => 'otherValue'));

        $this->assertEquals(0, $message1->getMetaData()->count());
        $this->assertEquals(1, $message2->getMetaData()->count());
    }

    /**
     * @covers Governor\Framework\Domain\GenericDomainEventMessage::andMetaData
     */
    public function testAndMetaData()
    {
        $payload = new Payload();
        $seqNo = 0;
        $id = Uuid::uuid1();
        $metaData = new MetaData(array('key' => 'value'));

        $message = new GenericDomainEventMessage($id, $seqNo, $payload,
            $metaData);
        $message1 = $message->andMetaData();
        $message2 = $message->andMetaData(array('key' => 'otherValue'));

        $this->assertEquals(1, $message1->getMetaData()->count());
        $this->assertEquals('value', $message1->getMetaData()->get('key'));
        $this->assertEquals(1, $message2->getMetaData()->count());
        $this->assertEquals('otherValue', $message2->getMetaData()->get('key'));
    }

}

class Payload
{
    
}
