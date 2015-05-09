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


namespace Governor\Tests\Domain;

use Governor\Framework\Domain\GenericEventMessage;
use Governor\Framework\Domain\MetaData;

/**
 * Description of GenericEventMessageTest
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
class GenericEventMessageTest extends \PHPUnit_Framework_TestCase
{

    public function testConstructor()
    {
        $payload = new \stdClass();
        $message1 = new GenericEventMessage($payload);

        $metaData = new MetaData(['key' => 'value']);
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
        $metaData = new MetaData(['key' => 'value']);

        $message = new GenericEventMessage($payload, $metaData);
        $message1 = $message->withMetaData();
        $message2 = $message->withMetaData(['key' => 'otherValue']);

        $this->assertEquals(0, $message1->getMetaData()->count());
        $this->assertEquals(1, $message2->getMetaData()->count());

        $this->assertEquals($message->getIdentifier(), $message1->getIdentifier());
        $this->assertEquals($message->getIdentifier(), $message2->getIdentifier());

        $this->assertInstanceOf(GenericEventMessage::class, $message1);
        $this->assertInstanceOf(GenericEventMessage::class, $message2);
    }

    public function testAndMetaData()
    {
        $payload = new \stdClass();
        $metaData = new MetaData(['key' => 'value']);

        $message = new GenericEventMessage($payload, $metaData);
        $message1 = $message->andMetaData();
        $message2 = $message->andMetaData(['key' => 'otherValue']);

        $this->assertEquals(1, $message1->getMetaData()->count());
        $this->assertEquals('value', $message1->getMetaData()->get('key'));
        $this->assertEquals(1, $message2->getMetaData()->count());
        $this->assertEquals('otherValue', $message2->getMetaData()->get('key'));

        $this->assertEquals($message->getIdentifier(), $message1->getIdentifier());
        $this->assertEquals($message->getIdentifier(), $message2->getIdentifier());

        $this->assertInstanceOf(GenericEventMessage::class, $message1);
        $this->assertInstanceOf(GenericEventMessage::class, $message2);
    }

}
