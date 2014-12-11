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

namespace Governor\Tests\Test\Matchers;

use Governor\Framework\Test\Matchers\Matchers;
use Governor\Tests\Test\MyEvent;
use Governor\Tests\Test\MyOtherEvent;
use Hamcrest\StringDescription;

/**
 * Description of EqualFieldsMatcherTest
 *
 * @author david
 */
class EqualFieldsMatcherTest extends \PHPUnit_Framework_TestCase
{

    private $testSubject;
    private $expectedEvent;
    private $aggregateId = "AggregateId";

    public function setUp()
    {
        $this->expectedEvent = new MyEvent($this->aggregateId, array("a" => "b"));
        $this->testSubject = Matchers::equalTo($this->expectedEvent);
    }

    public function testMatches_SameInstance()
    {
        $this->assertTrue($this->testSubject->matches($this->expectedEvent));
    }

    public function testMatches_EqualInstance()
    {
        $this->assertTrue(
            $this->testSubject->matches(
                new MyEvent(
                    $this->aggregateId,
                    array("a" => "b")
                )
            )
        );
    }

    public function testMatches_WrongEventType()
    {
        $this->assertFalse($this->testSubject->matches(new MyOtherEvent()));
    }

    public function testMatches_WrongFieldValue()
    {
        $this->assertFalse(
            $this->testSubject->matches(
                new MyEvent(
                    $this->aggregateId,
                    array("a" => "c")
                )
            )
        );
        $this->assertEquals("someValue", $this->testSubject->getFailedField());
    }

    public function testMatches_WrongFieldValueInArray()
    {
        $this->assertFalse(
            $this->testSubject->matches(
                new MyEvent(
                    $this->aggregateId,
                    array("c" => "d")
                )
            )
        );
        $this->assertEquals("someValue", $this->testSubject->getFailedField());
    }

    public function testDescription_AfterSuccess()
    {
        $this->testSubject->matches($this->expectedEvent);
        $description = new StringDescription();
        $this->testSubject->describeTo($description);
        $this->assertEquals(
            MyEvent::class,
            $description->__toString()
        );
    }

    public function testDescription_AfterMatchWithWrongType()
    {
        $this->testSubject->matches(new MyOtherEvent());
        $description = new StringDescription();
        $this->testSubject->describeTo($description);
        $this->assertEquals(
            MyEvent::class,
            $description->__toString()
        );
    }

    public function testDescription_AfterMatchWithWrongFieldValue()
    {
        $this->testSubject->matches(
            new MyEvent(
                $this->aggregateId,
                array("c" => "d")
            )
        );
        $description = new StringDescription();
        $this->testSubject->describeTo($description);
        $this->assertEquals(
            "Governor\Tests\Test\MyEvent (failed on field 'someValue')",
            $description->__toString()
        );
    }

}
