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

namespace Governor\Framework\Test;

use Hamcrest\Matcher;
use Hamcrest\StringDescription;
use Hamcrest\Core\IsNull;
use Governor\Framework\Test\Matchers\EqualFieldsMatcher;
use Governor\Framework\CommandHandling\CommandCallbackInterface;

/**
 * Description of ResultValidatorImpl
 *
 * @author david
 */
class ResultValidatorImpl implements ResultValidatorInterface, CommandCallbackInterface
{

    private $storedEvents;
    private $publishedEvents;
    private $actualReturnValue;
    private $actualException;
    private $reporter;

    function __construct(array &$storedEvents, array &$publishedEvents)
    {
        $this->storedEvents = &$storedEvents;
        $this->publishedEvents = &$publishedEvents;
        $this->reporter = new Reporter();
    }

    public function expectEvents(array $expectedEvents)
    {
        if (count($this->publishedEvents) !== count($this->storedEvents)) {
            $this->reporter->reportDifferenceInStoredVsPublished($this->storedEvents,
                $this->publishedEvents, $this->actualException);
        }

        return $this->expectPublishedEvents($expectedEvents);
    }

    public function expectEventsMatching(Matcher $matcher)
    {
        if (count($this->publishedEvents) !== count($this->storedEvents)) {
            $this->reporter->reportDifferenceInStoredVsPublished($this->storedEvents,
                $this->publishedEvents, $this->actualException);
        }

        return $this->expectPublishedEventsMatching($matcher);
    }

    public function expectException(Matcher $matcher)
    {
        $description = new StringDescription();
        $matcher->describeTo($description);

        if (null === $this->actualException) {
            $this->reporter->reportUnexpectedReturnValue($this->actualReturnValue,
                $description);
        }
        if (!$matcher->matches($this->actualException)) {
            $this->reporter->reportWrongException($this->actualException,
                $description);
        }
        return $this;
    }

    public function expectPublishedEvents(array $expectedEvents)
    {
        if (count($expectedEvents) !== count($this->publishedEvents)) {
            $this->reporter->reportWrongEvent($this->publishedEvents,
                $expectedEvents, $this->actualException);
        }

        foreach ($expectedEvents as $expectedEvent) {
            $actualEvent = current($this->publishedEvents);
            if (!$this->verifyEventEquality($expectedEvent,
                $actualEvent->getPayload())) {
                $this->reporter->reportWrongEvent($this->publishedEvents,
                    $expectedEvents, $this->actualException);
            }

            next($this->publishedEvents);
        }
        return $this;
    }

    public function expectPublishedEventsMatching(Matcher $matcher)
    {
        if (!$matcher->matches($this->publishedEvents)) {
            $this->reporter->reportWrongEventDescription($this->publishedEvents,
                $this->descriptionOf($matcher), $this->actualException);
        }
        return $this;
    }

    private function descriptionOf(Matcher $matcher)
    {
        $description = new StringDescription();
        $matcher->describeTo($description);
        return $description;
    }

    public function expectReturnValue(Matcher $matcher = null)
    {
        if (null === $matcher) {
            return $this->expectReturnValue(new IsNull());
        }

        $description = new StringDescription();
        $matcher->describeTo($description);

        if (null !== $this->actualException) {
            $this->reporter->reportUnexpectedException($this->actualException,
                $description);
        } else if (!$matcher->matches($this->actualReturnValue)) {
            $this->reporter->reportWrongResult($this->actualReturnValue,
                $description);
        }
        return $this;
    }

    public function expectStoredEvents(array $expectedEvents)
    {
        if (count($expectedEvents) !== count($this->storedEvents)) {
            $this->reporter->reportWrongEvent($this->storedEvents,
                $expectedEvents, $this->actualException);
        }

        foreach ($expectedEvents as $expectedEvent) {
            $actualEvent = current($this->storedEvents);
            if (!$this->verifyEventEquality($expectedEvent,
                $actualEvent->getPayload())) {
                $this->reporter->reportWrongEvent($this->storedEvents,
                    $expectedEvents, $this->actualException);
            }

            next($this->storedEvents);
        }
        return $this;
    }

    public function expectStoredEventsMatching(Matcher $matcher)
    {
        if (!$matcher->matches($this->storedEvents)) {
            $this->reporter->reportWrongEventDescription($this->storedEvents,
                $this->descriptionOf($matcher), $this->actualException);
        }
        return $this;
    }

    public function expectVoidReturnType()
    {
        return $this->expectReturnValue(null);
    }

    private function verifyEventEquality($expectedEvent, $actualEvent)
    {
        if (0 !== strcmp(get_class($expectedEvent), get_class($actualEvent))) {
            return false;
        }

        $matcher = new EqualFieldsMatcher($expectedEvent);

        if (!$matcher->matches($actualEvent)) {
            $this->reporter->reportDifferentEventContents(get_class($expectedEvent),
                $matcher->getFailedField(),
                $matcher->getFailedFieldActualValue(),
                $matcher->getFailedFieldExpectedValue());
        }

        return true;
    }

    public function onFailure(\Exception $cause)
    {
        $this->actualException = $cause;
    }

    public function onSuccess($result)
    {
        $this->actualReturnValue = $result;
    }

    /**
     * Makes sure the execution phase has finishes without any Errors ir FixtureExecutionExceptions. If an error was
     * recorded, it will be thrown immediately. This allow one to distinguish between failed tests, and tests in error.
     */
    public function assertValidRecording()
    {
        if ($this->actualException instanceof \Exception) {
            throw $this->actualException;
        }
    }

}
