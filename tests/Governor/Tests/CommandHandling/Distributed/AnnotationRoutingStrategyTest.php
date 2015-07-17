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

use Governor\Framework\Annotations\TargetAggregateIdentifier;
use Governor\Framework\CommandHandling\Distributed\UnresolvedRoutingKeyPolicy;
use Governor\Framework\CommandHandling\Distributed\AnnotationRoutingStrategy;
use Governor\Framework\CommandHandling\GenericCommandMessage;
use Governor\Framework\Common\Annotation\SimpleAnnotationReaderFactory;

class AnnotationRoutingStrategyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AnnotationRoutingStrategy
     */
    private $testSubject;


    public function setUp()
    {
        $this->testSubject = new AnnotationRoutingStrategy(new SimpleAnnotationReaderFactory());
    }


    public function testGetRoutingKey()
    {
        $actual = $this->testSubject->getRoutingKey(new GenericCommandMessage(new StubCommand("SomeIdentifier")));
        $this->assertEquals("SomeIdentifier", $actual);
    }


    /**
     * @expectedException \Governor\Framework\CommandHandling\Distributed\CommandDispatchException
     */
    public function testGetRoutingKey_NullKey()
    {
        $this->assertNull($this->testSubject->getRoutingKey(new GenericCommandMessage(new StubCommand(null))));
    }


    public function testGetRoutingKey_NullValueWithStaticPolicy()
    {
        $this->testSubject = new AnnotationRoutingStrategy(
            new SimpleAnnotationReaderFactory(),
            UnresolvedRoutingKeyPolicy::STATIC_KEY
        );
        $command = new GenericCommandMessage(new StubCommand(null));
        // two calls should provide the same result
        $this->assertEquals($this->testSubject->getRoutingKey($command), $this->testSubject->getRoutingKey($command));
    }

    /*
         public void testGetRoutingKey_NullValueWithRandomPolicy() throws Exception {
         testSubject = new AnnotationRoutingStrategy(UnresolvedRoutingKeyPolicy.RANDOM_KEY);
         CommandMessage<Object> command = new GenericCommandMessage<Object>(new Object());
             // two calls should provide the same result
             assertFalse(testSubject.getRoutingKey(command).equals(testSubject.getRoutingKey(command)));
         }
        */
}

class StubCommand
{


    /**
     * @TargetAggregateIdentifier
     */
    private $identifier;

    public function __construct($identifier)
    {
        $this->identifier = $identifier;
    }

    public function getIdentifier()
    {
        return $this->identifier;
    }
}