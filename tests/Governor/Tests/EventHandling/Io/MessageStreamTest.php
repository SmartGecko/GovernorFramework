<?php

/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * The software is based on the Axon Framework project which is
 * licensed under the Apache 2.0 license. For more information on the Axon Framework
 * see <http://www.axonframework.org/>.
 * 
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.governor-framework.org/>.
 */

namespace Governor\Tests\EventHandling\Io;

use Ramsey\Uuid\Uuid;
use JMS\Serializer\Annotation\Type;
use Governor\Framework\Serializer\JMSSerializer;
use Governor\Framework\Domain\MetaData;
use Governor\Framework\Domain\DomainEventMessageInterface;
use Governor\Framework\Domain\GenericEventMessage;
use Governor\Framework\Domain\GenericDomainEventMessage;
use Governor\Framework\EventHandling\Io\EventMessageWriter;
use Governor\Framework\EventHandling\Io\EventMessageReader;

/**
 * Description of MessageStreamTest
 *
 * @author david
 */
class MessageStreamTest extends \PHPUnit_Framework_TestCase
{

    public function testStreamEventMessage()
    {
        $serializer = new JMSSerializer();
        $writer = new EventMessageWriter($serializer);
        $payload = new StreamPayload("string", 10, 15.5);

        $message = new GenericEventMessage($payload,
                new MetaData(array("metaKey" => "MetaValue")));

        $data = $writer->writeEventMessage($message);
        $reader = new EventMessageReader($serializer);

        $serializedMessage = $reader->readEventMessage($data);

        $this->assertEquals($message->getIdentifier(),
                $serializedMessage->getIdentifier());
        $this->assertEquals($message->getPayloadType(),
                $serializedMessage->getPayloadType());
        $this->assertEquals($message->getTimestamp(),
                $serializedMessage->getTimestamp());
        $this->assertEquals($message->getMetaData(),
                $serializedMessage->getMetaData());
        $this->assertEquals($message->getPayload(),
                $serializedMessage->getPayload());
    }

    public function testStreamDomainEventMessage()
    {
        $serializer = new JMSSerializer();
        $writer = new EventMessageWriter($serializer);
        $payload = new StreamPayload("string", 10, 15.5);
        $message = new GenericDomainEventMessage(
                Uuid::uuid1(), 1, $payload,
                new MetaData(array("metaKey" =>
            "MetaValue")));
        $data = $writer->writeEventMessage($message);

        $reader = new EventMessageReader($serializer);

        $serializedMessage = $reader->readEventMessage($data);


        $this->assertTrue($serializedMessage instanceof DomainEventMessageInterface);

        $this->assertEquals($message->getIdentifier(),
                $serializedMessage->getIdentifier());
        $this->assertEquals($message->getPayloadType(),
                $serializedMessage->getPayloadType());
        $this->assertEquals($message->getTimestamp(),
                $serializedMessage->getTimestamp());
        $this->assertEquals($message->getMetaData(),
                $serializedMessage->getMetaData());
        $this->assertEquals($message->getPayload(),
                $serializedMessage->getPayload());
        $this->assertEquals($message->getAggregateIdentifier(),
                $serializedMessage->getAggregateIdentifier());
        $this->assertEquals($message->getScn(), $serializedMessage->getScn());
    }

}

class StreamPayload
{

    /**
     * @Type ("string")
     */
    private $string;

    /**
     * @Type ("integer")
     */
    private $integer;

    /**
     * @Type ("float")
     */
    private $float;

    function __construct($string, $integer, $float)
    {
        $this->string = $string;
        $this->integer = $integer;
        $this->float = $float;
    }

}
