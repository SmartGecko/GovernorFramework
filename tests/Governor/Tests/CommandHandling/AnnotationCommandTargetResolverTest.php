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

use Governor\Framework\Common\Annotation\SimpleAnnotationReaderFactory;
use Ramsey\Uuid\Uuid;
use Governor\Framework\CommandHandling\AnnotationCommandTargetResolver;
use Governor\Framework\Annotations\TargetAggregateIdentifier;
use Governor\Framework\Annotations\TargetAggregateVersion;
use Governor\Framework\CommandHandling\GenericCommandMessage;

class AnnotationCommandTargetResolverTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var AnnotationCommandTargetResolver
     */
    private $testSubject;

    public function setUp()
    {
        $this->testSubject = new AnnotationCommandTargetResolver(new SimpleAnnotationReaderFactory());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testResolveTarget_CommandWithoutAnnotations()
    {
        $this->testSubject->resolveTarget(GenericCommandMessage::asCommandMessage(new NotAnnotatedCommand()));
    }

    public function testResolveTarget_WithAnnotatedMethod()
    {
        $aggregateIdentifier = Uuid::uuid1();
        $actual = $this->testSubject->resolveTarget(
            GenericCommandMessage::asCommandMessage(
                new MethodAnnotatedCommand(
                    $aggregateIdentifier,
                    null
                )
            )
        );

        $this->assertSame($aggregateIdentifier, $actual->getIdentifier());
        $this->assertNull($actual->getVersion());
    }

    public function testResolveTarget_WithAnnotatedMethodAndVersion()
    {
        $aggregateIdentifier = Uuid::uuid1();
        $actual = $this->testSubject->resolveTarget(
            GenericCommandMessage::asCommandMessage(
                new MethodAnnotatedCommand(
                    $aggregateIdentifier,
                    1
                )
            )
        );

        $this->assertSame($aggregateIdentifier, $actual->getIdentifier());
        $this->assertEquals(1, $actual->getVersion());
    }

    public function testResolveTarget_WithAnnotatedFields()
    {
        $aggregateIdentifier = Uuid::uuid1();
        $version = 1;
        $actual = $this->testSubject->resolveTarget(
            GenericCommandMessage::asCommandMessage(
                new FieldAnnotatedCommand(
                    $aggregateIdentifier,
                    $version
                )
            )
        );
        $this->assertEquals($aggregateIdentifier, $actual->getIdentifier());
        $this->assertEquals($version, $actual->getVersion());
    }

    /*

      @Test(expected = IllegalArgumentException.class)
      public void testResolveTarget_WithAnnotatedFields_NonNumericVersion() {
      final UUID aggregateIdentifier = UUID.randomUUID();
      final Object version = "abc";
      testSubject.resolveTarget(asCommandMessage(new FieldAnnotatedCommand(aggregateIdentifier, version)));
      }

      } */
}

class FieldAnnotatedCommand
{

    /**
     * @TargetAggregateIdentifier
     */
    private $aggregateIdentifier;

    /**
     * @TargetAggregateVersion
     */
    private $version;

    public function __construct($aggregateIdentifier, $version)
    {
        $this->aggregateIdentifier = $aggregateIdentifier;
        $this->version = $version;
    }

    public function getAggregateIdentifier()
    {
        return $this->aggregateIdentifier;
    }

    public function getVersion()
    {
        return $this->version;
    }

}

class NotAnnotatedCommand
{

}

class MethodAnnotatedCommand
{

    private $aggregateIdentifier;
    private $version;

    public function __construct($aggregateIdentifier, $version)
    {
        $this->aggregateIdentifier = $aggregateIdentifier;
        $this->version = $version;
    }

    /**
     * @TargetAggregateIdentifier
     */
    public function getAggregateIdentifier()
    {
        return $this->aggregateIdentifier;
    }

    /**
     * @TargetAggregateVersion
     */
    public function getVersion()
    {
        return $this->version;
    }

}
