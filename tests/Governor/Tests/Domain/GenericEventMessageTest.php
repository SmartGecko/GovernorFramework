<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Tests\Domain;

use Governor\Framework\Domain\GenericEventMessage;
use Governor\Framework\Domain\MetaData;

/**
 * Description of GenericEventMessageTest
 *
 * @author david
 */
class GenericEventMessageTest extends \PHPUnit_Framework_TestCase
{

    public function testConstructor()
    {
        $payload = new \stdClass();
        $message1 = new GenericEventMessage($payload);

        $metaData = new MetaData(array("key" => "value"));
        $message2 = new GenericEventMessage($payload, $metaData);

        $this->assertSame(MetaData::emptyInstance(), $message1->getMetaData());
        $this->assertEquals('stdClass', get_class($message1->getPayload()));
        $this->assertEquals('stdClass', $message1->getPayloadType());
        $this->assertSame($payload, $message1->getPayload());

        $this->assertSame($metaData, $message2->getMetaData());
        $this->assertEquals('stdClass', get_class($message2->getPayload()));
        $this->assertEquals('stdClass', $message2->getPayloadType());
        $this->assertEquals($payload, $message2->getPayload());

        $this->assertFalse($message1->getIdentifier() === $message2->getIdentifier());
    }

    public function testWithMetaData()
    {
        $payload = new \stdClass();
        $metaData = new MetaData(array("key" => "value"));

        $message = new GenericEventMessage($payload, $metaData);
        $message1 = $message->withMetaData();
        $message2 = $message->withMetaData(
            array("key" => "otherValue"));

        $this->assertEquals(0, $message1->getMetaData()->count());
        $this->assertEquals(1, $message2->getMetaData()->count());
    }

    public function testAndMetaData()
    {
        $payload = new \stdClass();
        $metaData = new MetaData(array("key" => "value"));

        $message = new GenericEventMessage($payload, $metaData);
        $message1 = $message->andMetaData();
        $message2 = $message->andMetaData(array("key" => "otherValue"));

        $this->assertEquals(1, $message1->getMetaData()->count());
        $this->assertEquals("value", $message1->getMetaData()->get("key"));
        $this->assertEquals(1, $message2->getMetaData()->count());
        $this->assertEquals("otherValue", $message2->getMetaData()->get("key"));
    }

}
