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

use Hamcrest\Matcher;
use Hamcrest\Description;
use Hamcrest\StringDescription;
use Governor\Framework\Test\Matchers\Matchers;

/**
 * Description of ExactSequenceOfEventsMatcherTest
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a> 
 */
class ExactSequenceOfEventsMatcherTest extends \PHPUnit_Framework_TestCase
{

    private $mockMatcher1;
    private $mockMatcher2;
    private $mockMatcher3;
    private $testSubject;
    private $stubEvent1;
    private $stubEvent2;
    private $stubEvent3;

    public function setUp()
    {
        $this->mockMatcher1 = \Phake::mock(Matcher::class);
        $this->mockMatcher2 = \Phake::mock(Matcher::class);
        $this->mockMatcher3 = \Phake::mock(Matcher::class);
        $this->testSubject = Matchers::exactSequenceOf(array($this->mockMatcher1,
                    $this->mockMatcher2, $this->mockMatcher3));

        $this->stubEvent1 = new StubEvent1();
        $this->stubEvent2 = new StubEvent2();
        $this->stubEvent3 = new StubEvent3();

        \Phake::when($this->mockMatcher1)->matches(\Phake::anyParameters())->thenReturn(true);
        \Phake::when($this->mockMatcher2)->matches(\Phake::anyParameters())->thenReturn(true);
        \Phake::when($this->mockMatcher3)->matches(\Phake::anyParameters())->thenReturn(true);
    }

    public function testMatch_FullMatch()
    {
        $this->assertTrue($this->testSubject->matches(array($this->stubEvent1, $this->stubEvent2,
                    $this->stubEvent3)));

        \Phake::verify($this->mockMatcher1, \Phake::times(1))->matches($this->stubEvent1);
        \Phake::verify($this->mockMatcher1, \Phake::never())->matches($this->stubEvent2);
        \Phake::verify($this->mockMatcher1, \Phake::never())->matches($this->stubEvent3);

        \Phake::verify($this->mockMatcher2, \Phake::never())->matches($this->stubEvent1);
        \Phake::verify($this->mockMatcher2, \Phake::times(1))->matches($this->stubEvent2);
        \Phake::verify($this->mockMatcher2, \Phake::never())->matches($this->stubEvent3);

        \Phake::verify($this->mockMatcher3, \Phake::never())->matches($this->stubEvent1);
        \Phake::verify($this->mockMatcher3, \Phake::never())->matches($this->stubEvent2);
        \Phake::verify($this->mockMatcher3, \Phake::times(1))->matches($this->stubEvent3);
    }

    public function testMatch_FullMatchAndNoMore()
    {
        $this->testSubject = Matchers::exactSequenceOf(array($this->mockMatcher1,
                    $this->mockMatcher2, $this->mockMatcher3, Matchers::andNoMore()));
        $this->assertTrue($this->testSubject->matches(array($this->stubEvent1, $this->stubEvent2,
                    $this->stubEvent3)));

        \Phake::verify($this->mockMatcher1, \Phake::times(1))->matches($this->stubEvent1);
        \Phake::verify($this->mockMatcher1, \Phake::never())->matches($this->stubEvent2);
        \Phake::verify($this->mockMatcher1, \Phake::never())->matches($this->stubEvent3);

        \Phake::verify($this->mockMatcher2, \Phake::never())->matches($this->stubEvent1);
        \Phake::verify($this->mockMatcher2, \Phake::times(1))->matches($this->stubEvent2);
        \Phake::verify($this->mockMatcher2, \Phake::never())->matches($this->stubEvent3);

        \Phake::verify($this->mockMatcher3, \Phake::never())->matches($this->stubEvent1);
        \Phake::verify($this->mockMatcher3, \Phake::never())->matches($this->stubEvent2);
        \Phake::verify($this->mockMatcher3, \Phake::times(1))->matches($this->stubEvent3);
    }

    public function testMatch_ExcessIsRefused()
    {
        $this->testSubject = Matchers::exactSequenceOf(array($this->mockMatcher1,
                    $this->mockMatcher2, $this->mockMatcher3, Matchers::andNoMore()));
        $this->assertFalse($this->testSubject->matches(array($this->stubEvent1, $this->stubEvent2,
                    $this->stubEvent3, new StubEvent())));

        \Phake::verify($this->mockMatcher1, \Phake::times(1))->matches($this->stubEvent1);
        \Phake::verify($this->mockMatcher1, \Phake::never())->matches($this->stubEvent2);
        \Phake::verify($this->mockMatcher1, \Phake::never())->matches($this->stubEvent3);

        \Phake::verify($this->mockMatcher2, \Phake::never())->matches($this->stubEvent1);
        \Phake::verify($this->mockMatcher2, \Phake::times(1))->matches($this->stubEvent2);
        \Phake::verify($this->mockMatcher2, \Phake::never())->matches($this->stubEvent3);

        \Phake::verify($this->mockMatcher3, \Phake::never())->matches($this->stubEvent1);
        \Phake::verify($this->mockMatcher3, \Phake::never())->matches($this->stubEvent2);
        \Phake::verify($this->mockMatcher3, \Phake::times(1))->matches($this->stubEvent3);
    }

    public function testMatch_FullMatchWithGaps()
    {
        \Phake::reset($this->mockMatcher2);
        \Phake::when($this->mockMatcher2)->matches(\Phake::anyParameters())->thenReturn(false);


        $this->assertFalse($this->testSubject->matches(array($this->stubEvent1, $this->stubEvent2,
                    $this->stubEvent3)));

        \Phake::verify($this->mockMatcher1, \Phake::times(1))->matches($this->stubEvent1);
        \Phake::verify($this->mockMatcher1, \Phake::never())->matches($this->stubEvent2);
        \Phake::verify($this->mockMatcher1, \Phake::never())->matches($this->stubEvent3);

        \Phake::verify($this->mockMatcher2, \Phake::never())->matches($this->stubEvent1);
        \Phake::verify($this->mockMatcher2, \Phake::times(1))->matches($this->stubEvent2);
        \Phake::verify($this->mockMatcher2, \Phake::never())->matches($this->stubEvent3);

        \Phake::verify($this->mockMatcher3, \Phake::never())->matches(\Phake::anyParameters());
    }

    public function testMatch_MoreMatchersThanEvents()
    {
        \Phake::when($this->mockMatcher3)->matches(null)->thenReturn(false);
        $this->assertFalse($this->testSubject->matches(array($this->stubEvent1, $this->stubEvent2)));

        \Phake::verify($this->mockMatcher1, \Phake::times(1))->matches($this->stubEvent1);
        \Phake::verify($this->mockMatcher1, \Phake::never())->matches($this->stubEvent2);

        \Phake::verify($this->mockMatcher2, \Phake::never())->matches($this->stubEvent1);
        \Phake::verify($this->mockMatcher2, \Phake::times(1))->matches($this->stubEvent2);

        \Phake::verify($this->mockMatcher3, \Phake::never())->matches($this->stubEvent1);
        \Phake::verify($this->mockMatcher3, \Phake::never())->matches($this->stubEvent2);
        \Phake::verify($this->mockMatcher3, \Phake::times(1))->matches(null);
    }

    public function testMatch_ExcessEventsIgnored()
    {
        $this->assertTrue($this->testSubject->matches(array($this->stubEvent1, $this->stubEvent2,
                    $this->stubEvent3,
                    new StubEvent())));

        \Phake::verify($this->mockMatcher1, \Phake::times(1))->matches($this->stubEvent1);
        \Phake::verify($this->mockMatcher1, \Phake::never())->matches($this->stubEvent2);

        \Phake::verify($this->mockMatcher2, \Phake::never())->matches($this->stubEvent1);
        \Phake::verify($this->mockMatcher2, \Phake::times(1))->matches($this->stubEvent2);

        \Phake::verify($this->mockMatcher3, \Phake::never())->matches($this->stubEvent1);
        \Phake::verify($this->mockMatcher3, \Phake::never())->matches($this->stubEvent2);
        \Phake::verify($this->mockMatcher3, \Phake::times(1))->matches($this->stubEvent3);
    }

    public function testDescribe()
    {
        $this->testSubject->matches(array($this->stubEvent1, $this->stubEvent2));

        \Phake::when($this->mockMatcher1)->describeTo(\Phake::anyParameters())->thenGetReturnByLambda(function (Description $description) {
            $description->appendText("A");
        });
        \Phake::when($this->mockMatcher2)->describeTo(\Phake::anyParameters())->thenGetReturnByLambda(function (Description $description) {
            $description->appendText("B");
        });
        \Phake::when($this->mockMatcher3)->describeTo(\Phake::anyParameters())->thenGetReturnByLambda(function (Description $description) {
            $description->appendText("C");
        });

        $description = new StringDescription();
        $this->testSubject->describeTo($description);
        $actual = $description->__toString();
        $this->assertEquals("list with exact sequence of: <A>, <B> and <C>",
                $actual);
    }

    public function testDescribe_OneMatcherFailed()
    {
        \Phake::when($this->mockMatcher1)->matches(\Phake::anyParameters())->thenReturn(true);
        \Phake::when($this->mockMatcher2)->matches(\Phake::anyParameters())->thenReturn(false);
        \Phake::when($this->mockMatcher3)->matches(\Phake::anyParameters())->thenReturn(false);

        $this->testSubject->matches(array($this->stubEvent1, $this->stubEvent2));

        \Phake::when($this->mockMatcher1)->describeTo(\Phake::anyParameters())->thenGetReturnByLambda(function (Description $description) {
            $description->appendText("A");
        });
        \Phake::when($this->mockMatcher2)->describeTo(\Phake::anyParameters())->thenGetReturnByLambda(function (Description $description) {
            $description->appendText("B");
        });
        \Phake::when($this->mockMatcher3)->describeTo(\Phake::anyParameters())->thenGetReturnByLambda(function (Description $description) {
            $description->appendText("C");
        });
        $description = new StringDescription();
        $this->testSubject->describeTo($description);
        $actual = $description->__toString();
        $this->assertEquals("list with exact sequence of: <A>, <B> (FAILED!) and <C>",
                $actual);
    }

}
