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

namespace Governor\Tests\CommandHandling\Distributed;

use JMS\Serializer\Annotation\Type;
use Governor\Framework\Serializer\JMSSerializer;
use Governor\Framework\CommandHandling\Distributed\ReplyMessage;

class ReplyMessageTest extends \PHPUnit_Framework_TestCase
{

    const COMMAND_ID = 'e09381d0-0381-11e5-b5b7-406c8f20ad00';

    public function testScalarResult()
    {
        $serializer = new JMSSerializer();

        $replyMessage1 = new ReplyMessage(self::COMMAND_ID, $serializer, "return");
        $bytes = $replyMessage1->toBytes();

        $replyMessage2 = ReplyMessage::fromBytes($serializer, $bytes);

        $this->assertEquals($replyMessage1->getReturnValue(), $replyMessage2->getReturnValue());
        $this->assertEquals($replyMessage1->getCommandIdentifier(), $replyMessage2->getCommandIdentifier());
        $this->assertTrue($replyMessage1->isSuccess());
        $this->assertTrue($replyMessage2->isSuccess());
    }

    public function testObjectResult()
    {
        $serializer = new JMSSerializer();

        $replyMessage1 = new ReplyMessage(self::COMMAND_ID, $serializer, new ResultPayload("string", 10, 1.1));
        $bytes = $replyMessage1->toBytes();

        $replyMessage2 = ReplyMessage::fromBytes($serializer, $bytes);

        $this->assertEquals($replyMessage1->getReturnValue(), $replyMessage2->getReturnValue());
        $this->assertEquals($replyMessage1->getCommandIdentifier(), $replyMessage2->getCommandIdentifier());
        $this->assertTrue($replyMessage1->isSuccess());
        $this->assertTrue($replyMessage2->isSuccess());
    }

    public function testNullResult()
    {
        $serializer = new JMSSerializer();

        $replyMessage1 = new ReplyMessage(self::COMMAND_ID, $serializer, null);
        $bytes = $replyMessage1->toBytes();

        $replyMessage2 = ReplyMessage::fromBytes($serializer, $bytes);

        $this->assertEquals($replyMessage1->getReturnValue(), $replyMessage2->getReturnValue());
        $this->assertNull($replyMessage2->getReturnValue());
        $this->assertEquals($replyMessage1->getCommandIdentifier(), $replyMessage2->getCommandIdentifier());
        $this->assertTrue($replyMessage1->isSuccess());
        $this->assertTrue($replyMessage2->isSuccess());
    }

    public function testExceptionResult()
    {
        $serializer = new JMSSerializer();

        $replyMessage1 = new ReplyMessage(self::COMMAND_ID, $serializer, new \RuntimeException("Exception !!!"), false);
        $bytes = $replyMessage1->toBytes();

        $replyMessage2 = ReplyMessage::fromBytes($serializer, $bytes);

        $this->assertEquals($replyMessage1->getReturnValue(), $replyMessage2->getReturnValue());
        $this->assertEquals($replyMessage1->getError(), $replyMessage2->getError());
        $this->assertEquals($replyMessage1->getCommandIdentifier(), $replyMessage2->getCommandIdentifier());
        $this->assertFalse($replyMessage1->isSuccess());
        $this->assertFalse($replyMessage2->isSuccess());
    }
}

class ResultPayload
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