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

namespace Governor\Framework\Test\Matchers;

use Hamcrest\Matcher;

/**
 * Description of ExactSequenceOfEventsMatcherTest
 *
 * @author david
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
        $this->testSubject = Matchers::sequenceOf(array($this->mockMatcher1,
                    $this->mockMatcher2, $this->mockMatcher3));
        
        $this->stubEvent1 = new StubEvent();
        $this->stubEvent2 = new StubEvent();
        $this->stubEvent3 = new StubEvent();

        \Phake::when($this->mockMatcher1)->matches(\Phake::anyParameters())->thenReturn(true);
        \Phake::when($this->mockMatcher2)->matches(\Phake::anyParameters())->thenReturn(true);
        \Phake::when($this->mockMatcher3)->matches(\Phake::anyParameters())->thenReturn(true);
    }

    public function testMatch_FullMatch()
    {
        $this->assertTrue($this->testSubject->matches(array($this->stubEvent1, $this->stubEvent2,
                    $this->stubEvent3)));

        \Phake::verify($this->mockMatcher1, \Phake::times(1))->matches($this->stubEvent1);
     //   \Phake::verify($this->mockMatcher1, \Phake::never())->matches($this->stubEvent2);
      //  \Phake::verify($this->mockMatcher1, \Phake::never())->matches($this->stubEvent3);
        
        /*
          verify(mockMatcher1).matches(stubEvent1);
          verify(mockMatcher1, never()).matches(stubEvent2);
          verify(mockMatcher1, never()).matches(stubEvent3);

          verify(mockMatcher2, never()).matches(stubEvent1);
          verify(mockMatcher2).matches(stubEvent2);
          verify(mockMatcher2, never()).matches(stubEvent3);

          verify(mockMatcher3, never()).matches(stubEvent1);
          verify(mockMatcher3, never()).matches(stubEvent2);
          verify(mockMatcher3).matches(stubEvent3); */
    }

    public function testMatch_FullMatchAndNoMore()
    {
        /*  $this->testSubject = Matchers::exactSequenceOf(array($this->mockMatcher1,
          $this->mockMatcher2, $this->mockMatcher3, Matchers::andNoMore()));
          $this->assertTrue($this->testSubject->matches(array($this->stubEvent1, $this->stubEvent2,
          $this->stubEvent3)));
          /*
          verify(mockMatcher1).matches(stubEvent1);
          verify(mockMatcher1, never()).matches(stubEvent2);
          verify(mockMatcher1, never()).matches(stubEvent3);

          verify(mockMatcher2, never()).matches(stubEvent1);
          verify(mockMatcher2).matches(stubEvent2);
          verify(mockMatcher2, never()).matches(stubEvent3);

          verify(mockMatcher3, never()).matches(stubEvent1);
          verify(mockMatcher3, never()).matches(stubEvent2);
          verify(mockMatcher3).matches(stubEvent3); */
    }

    public function testMatch_ExcessIsRefused()
    {
        /* $this->testSubject = Matchers::exactSequenceOf(array($this->mockMatcher1,
          $this->mockMatcher2, $this->mockMatcher3, Matchers::andNoMore()));
          $this->assertFalse($this->testSubject->matches(array($this->stubEvent1, $this->stubEvent2,
          $this->stubEvent3, new StubEvent())));

          /*  verify(mockMatcher1).matches(stubEvent1);
          verify(mockMatcher1, never()).matches(stubEvent2);
          verify(mockMatcher1, never()).matches(stubEvent3);

          verify(mockMatcher2, never()).matches(stubEvent1);
          verify(mockMatcher2).matches(stubEvent2);
          verify(mockMatcher2, never()).matches(stubEvent3);

          verify(mockMatcher3, never()).matches(stubEvent1);
          verify(mockMatcher3, never()).matches(stubEvent2);
          verify(mockMatcher3).matches(stubEvent3); */
    }

    /*
      @Test
      public void testMatch_FullMatchWithGaps() {
      reset(mockMatcher2);
      when(mockMatcher2.matches(any())).thenReturn(false);

      assertFalse(testSubject.matches(Arrays.asList(stubEvent1, stubEvent2, stubEvent3)));

      verify(mockMatcher1).matches(stubEvent1);
      verify(mockMatcher1, never()).matches(stubEvent2);
      verify(mockMatcher1, never()).matches(stubEvent3);

      verify(mockMatcher2, never()).matches(stubEvent1);
      verify(mockMatcher2).matches(stubEvent2);
      verify(mockMatcher2, never()).matches(stubEvent3);

      verify(mockMatcher3, never()).matches(any());
      }

      @Test
      public void testMatch_MoreMatchersThanEvents() {
      when(mockMatcher3.matches(null)).thenReturn(false);
      assertFalse(testSubject.matches(Arrays.asList(stubEvent1, stubEvent2)));

      verify(mockMatcher1).matches(stubEvent1);
      verify(mockMatcher1, never()).matches(stubEvent2);
      verify(mockMatcher2, never()).matches(stubEvent1);
      verify(mockMatcher2).matches(stubEvent2);
      verify(mockMatcher3, never()).matches(stubEvent1);
      verify(mockMatcher3, never()).matches(stubEvent2);
      verify(mockMatcher3).matches(null);
      }

      @Test
      public void testMatch_ExcessEventsIgnored() {
      assertTrue(testSubject.matches(Arrays.asList(stubEvent1, stubEvent2, stubEvent3, new StubEvent())));

      verify(mockMatcher1).matches(stubEvent1);
      verify(mockMatcher1, never()).matches(stubEvent2);
      verify(mockMatcher2, never()).matches(stubEvent1);
      verify(mockMatcher2).matches(stubEvent2);
      verify(mockMatcher3, never()).matches(stubEvent1);
      verify(mockMatcher3, never()).matches(stubEvent2);
      verify(mockMatcher3).matches(stubEvent3);
      }

      @Test
      public void testDescribe() {
      testSubject.matches(Arrays.asList(stubEvent1, stubEvent2));

      doAnswer(new DescribingAnswer("A")).when(mockMatcher1).describeTo(isA(Description.class));
      doAnswer(new DescribingAnswer("B")).when(mockMatcher2).describeTo(isA(Description.class));
      doAnswer(new DescribingAnswer("C")).when(mockMatcher3).describeTo(isA(Description.class));
      StringDescription description = new StringDescription();
      testSubject.describeTo(description);
      String actual = description.toString();
      assertEquals("list with exact sequence of: <A>, <B> and <C>", actual);
      }

      @Test
      public void testDescribe_OneMatcherFailed() {
      when(mockMatcher1.matches(any())).thenReturn(true);
      when(mockMatcher2.matches(any())).thenReturn(false);
      when(mockMatcher3.matches(any())).thenReturn(false);

      testSubject.matches(Arrays.asList(stubEvent1, stubEvent2));

      doAnswer(new DescribingAnswer("A")).when(mockMatcher1).describeTo(isA(Description.class));
      doAnswer(new DescribingAnswer("B")).when(mockMatcher2).describeTo(isA(Description.class));
      doAnswer(new DescribingAnswer("C")).when(mockMatcher3).describeTo(isA(Description.class));
      StringDescription description = new StringDescription();
      testSubject.describeTo(description);
      String actual = description.toString();
      assertEquals("list with exact sequence of: <A>, <B> (FAILED!) and <C>", actual);
      }

      private static class DescribingAnswer implements Answer<Object> {
      private String description;

      public DescribingAnswer(String description) {
      this.description = description;
      }

      @Override
      public Object answer(InvocationOnMock invocation) throws Throwable {
      Description descriptionParameter = (Description) invocation.getArguments()[0];
      descriptionParameter.appendText(this.description);
      return Void.class;
      }
      }
     */
}
