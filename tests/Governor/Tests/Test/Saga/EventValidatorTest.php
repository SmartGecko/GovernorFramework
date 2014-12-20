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

namespace Governor\Tests\Test\Saga;

use Governor\Framework\Domain\GenericEventMessage;
use Governor\Framework\Test\Matchers\Matchers;
use Governor\Framework\Test\Saga\EventValidator;
use Governor\Tests\Test\MyOtherEvent;

class EventValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EventValidator
     */
    private $testSubject;

    public function setUp()
    {
        $this->testSubject = new EventValidator(null);
    }

    public function testAssertPublishedEventsWithNoEventsMatcherIfNoEventWasPublished()
    {
        $this->testSubject->assertPublishedEventsMatching(Matchers::noEvents());
    }

    /**
     * @expectedException \Governor\Framework\Test\GovernorAssertionError
     */
    public function testAssertPublishedEventsWithNoEventsMatcherThrowsAssertionErrorIfEventWasPublished()
    {
        $this->testSubject->handle(new GenericEventMessage(new MyOtherEvent()));

        $this->testSubject->assertPublishedEventsMatching(Matchers::noEvents());
    }


    public function testAssertPublishedEventsIfNoEventWasPublished()
    {
        $this->testSubject->assertPublishedEvents();
    }

    /**
     * @expectedException \Governor\Framework\Test\GovernorAssertionError
     */
    public function testAssertPublishedEventsThrowsAssertionErrorIfEventWasPublished()
    {
        $this->testSubject->handle(new GenericEventMessage(new MyOtherEvent()));

        $this->testSubject->assertPublishedEvents();
    }

}