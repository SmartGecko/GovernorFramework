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

use Governor\Framework\CommandHandling\Distributed\DispatchMessage;
use Governor\Framework\CommandHandling\GenericCommandMessage;
use JMS\Serializer\Annotation\Type;
use Governor\Framework\Serializer\JMSSerializer;

class DispatchMessageTest extends \PHPUnit_Framework_TestCase
{
    public function testFromAndToBytes()
    {
        $serializer = new JMSSerializer();
        $commandMessage = GenericCommandMessage::asCommandMessage(new ResultPayload("string", 10, 5.1));

        $dispatchMessage1 = new DispatchMessage($commandMessage, $serializer, false);
        $bytes = $dispatchMessage1->toBytes();

        $dispatchMessage2 = DispatchMessage::fromBytes($serializer, $bytes);

        $this->assertEquals($dispatchMessage1->getCommandIdentifier(), $dispatchMessage2->getCommandIdentifier());
        $this->assertEquals($dispatchMessage1->isExpectReply(), $dispatchMessage2->isExpectReply());
        $this->assertEquals($dispatchMessage1->getCommandMessage(), $dispatchMessage2->getCommandMessage());
        $this->assertFalse($dispatchMessage1->isExpectReply());
        $this->assertFalse($dispatchMessage2->isExpectReply());
    }
}

class DispatchPayload
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
