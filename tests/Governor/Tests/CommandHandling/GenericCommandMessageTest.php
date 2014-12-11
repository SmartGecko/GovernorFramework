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

namespace Governor\Tests\CommandHandling;

use Governor\Framework\Domain\MetaData;
use Governor\Framework\CommandHandling\GenericCommandMessage;

/**
 * Description of GenericCommandMessageTest
 *
 * @author david
 */
class GenericCommandMessageTest extends \PHPUnit_Framework_TestCase
{

    public function testConstructor()
    {
        $payload = new \stdClass();
        $message1 = new GenericCommandMessage($payload);
        $metaDataMap = array("key" => "value");

        $metaData = new MetaData($metaDataMap);
        $message2 = new GenericCommandMessage($payload, $metaData);

        $this->assertSame(MetaData::emptyInstance(), $message1->getMetaData());
        $this->assertEquals('stdClass', get_class($message1->getPayload()));
        $this->assertEquals('stdClass', $message1->getPayloadType());

        $this->assertSame($metaData, $message2->getMetaData());
        $this->assertEquals('stdClass', get_class($message2->getPayload()));
        $this->assertEquals('stdClass', $message2->getPayloadType());


        $this->assertFalse($message1->getIdentifier() === $message2->getIdentifier());
    }

    public function testWithMetaData()
    {
        $payload = new \stdClass();
        $metaDataMap = array("key" => "value");
        $metaData = new MetaData($metaDataMap);
        $message = new GenericCommandMessage($payload, $metaData);
        $message1 = $message->withMetaData();
        $message2 = $message->withMetaData(
                array("key" => "otherValue"));

        $this->assertEquals(0, count($message1->getMetaData()));
        $this->assertEquals(1, count($message2->getMetaData()));
    }

    public function testAndMetaData()
    {
        $payload = new \stdClass();
        $metaDataMap = array("key" => "value");
        $metaData = new MetaData($metaDataMap);
        $message = new GenericCommandMessage($payload, $metaData);
        $message1 = $message->andMetaData();
        $message2 = $message->andMetaData(array("key" => "otherValue"));

        $this->assertEquals(1, count($message1->getMetaData()));
        $this->assertEquals("value", $message1->getMetaData()->get("key"));
        $this->assertEquals(1, count($message2->getMetaData()));
        $this->assertEquals("otherValue", $message2->getMetaData()->get("key"));
    }

}
