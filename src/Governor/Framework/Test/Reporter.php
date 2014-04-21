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

use Hamcrest\Description;
use Governor\Framework\Domain\EventMessageInterface;

/**
 * The reporter generates extensive human readable reports of what the expected outcome of a test was, and what the
 * actual results were.

 */
class Reporter
{

    /**
     * Report a failed assertion due to a difference in the stored versus the published events.
     *
     * @param storedEvents    The events that were stored
     * @param publishedEvents The events that were published
     * @param probableCause   An exception that might be the cause of the failure
     */
    public function reportDifferenceInStoredVsPublished(array $storedEvents,
            array $publishedEvents, \Exception $probableCause)
    {
        $str = "The stored events do not match the published events.";
        $str .= $this->appendEventOverview($storedEvents, $publishedEvents,
                "Stored events", "Published events");
        $str .= $this->appendProbableCause($probableCause);

        throw new GovernorAssertionError($str);
    }

    /**
     * Report an error in the ordering or count of events. This is typically a difference that can be shown to the user
     * by enumerating the expected and actual events
     *
     * @param actualEvents   The events that were found
     * @param expectedEvents The events that were expected
     * @param probableCause  An optional exception that might be the reason for wrong events
     */
    public function reportWrongEvent(array $actualEvents, array $expectedEvents,
            \Exception $probableCause = null)
    {
        $str = "The published events do not match the expected events";
        $str .= $this->appendEventOverview($expectedEvents, $actualEvents,
                "Expected", "Actual");
        $str .= $this->appendProbableCause($probableCause);

        throw new GovernorAssertionError($str);
    }

    /**
     * Report an error in the ordering or count of events. This is typically a difference that can be shown to the user
     * by enumerating the expected and actual events
     *
     * @param actualEvents  The events that were found
     * @param expectation   A Description of what was expected
     * @param probableCause An optional exception that might be the reason for wrong events
     */
    /*  public function reportWrongEvent(array $actualEvents, $expectation,
      \Exception $probableCause)
      {
      /* StringBuilder sb = new StringBuilder(
      "The published events do not match the expected events.");
      sb.append("Expected :");
      sb.append(NEWLINE);
      sb.append(expectation);
      sb.append(NEWLINE);
      sb.append("But got");
      if (actualEvents.isEmpty()) {
      sb.append(" none");
      } else {
      sb.append(":");
      }
      for (Object publishedEvent : actualEvents) {
      sb.append(NEWLINE);
      sb.append(publishedEvent.getClass().getSimpleName());
      sb.append(": ");
      sb.append(publishedEvent.toString());
      }
      appendProbableCause(probableCause, sb);

      throw new AxonAssertionError(sb.toString());
      } */

    /**
     * Reports an error due to an unexpected exception. This means a return value was expected, but an exception was
     * thrown by the command handler
     *
     * @param actualException The actual exception
     * @param expectation     A text describing what was expected
     */
    public function reportUnexpectedException(\Exception $actualException,
            Description $expectation)
    {
        $str = "The command handler threw an unexpected exception";
        $str .= PHP_EOL . PHP_EOL;
        $str .= "Expected <" . $expectation . "> but got <exception of type [";
        $str .= get_class($actualException) . "]>. Stack trace follows:" . PHP_EOL;
        $str .= $this->writeStackTrace($actualException) . PHP_EOL;

        throw new GovernorAssertionError($str);
    }

    /**
     * Reports an error due to a wrong return value.
     *
     * @param actualReturnValue The actual return value
     * @param expectation       A description of the expected value
     */
    public function reportWrongResult($actualReturnValue,
            Description $expectation)
    {
        $str = "The command handler returned an unexpected value";
        $str .= PHP_EOL . PHP_EOL;
        $str .= "Expected <" . $expectation . "> but got <";
        $str .= $this->describe($actualReturnValue) . ">";
        $str .= PHP_EOL;

        throw new GovernorAssertionError($str);
    }

    /**
     * Report an error due to an unexpected return value, while an exception was expected.
     *
     * @param actualReturnValue The actual return value
     * @param Description $description       A description describing the expected value
     */
    public function reportUnexpectedReturnValue($actualReturnValue,
            Description $description)
    {
        $str = "The command handler returned normally, but an exception was expected";
        $str .= PHP_EOL . PHP_EOL;
        $str .= "Expected <" . $description . "> but returned with <" . $actualReturnValue;
        $str .= $this->describe($actualReturnValue) . ">";
        $str .= PHP_EOL;

        throw new GovernorAssertionError($str);
    }

    /**
     * Report an error due to a an exception of an unexpected type.
     *
     * @param \Exception $actualException The actual exception
     * @param Description $description     A description describing the expected value
     */
    public function reportWrongException(\Exception $actualException,
            Description $description)
    {
        $str .= "The command handler threw an exception, but not of the expected type";
        $str .= PHP_EOL . PHP_EOL;
        $str .= "Expected <" . $description . "> but got <exception of type [";
        $str .= get_class($actualException) . "]>. Stacktrace follows: ";
        $str .= PHP_EOL . $this->writeStackTrace($actualException) . PHP_EOL;

        throw new GovernorAssertionError($str);
    }

    /**
     * Report an error due to a difference in on of the fields of an event.
     *
     * @param eventType The (runtime) type of event the difference was found in
     * @param field     The field that contains the difference
     * @param actual    The actual value of the field
     * @param expected  The expected value of the field
     */
    public function reportDifferentEventContents($eventType, $field, $actual,
            $expected)
    {
        $str = "One of the events contained different values than expected";
        $str .= PHP_EOL . PHP_EOL;
        $str .= "In an event of type [" . $eventType . "], the property [";
        $str .= $field . "] ";

        /* if (!strcmp($eventType.equals(field.getDeclaringClass())) {
          sb.append("(declared in [")
          .append(field.getDeclaringClass().getSimpleName())
          .append("]) ");
          } */

        $str .= "was not as expected." . PHP_EOL;
        $str .= "Expected <" . 
                $this->nullSafeToString($expected) . 
                "> but got <" . 
                $this->nullSafeToString($actual) . 
                ">" . 
                PHP_EOL;

        throw new GovernorAssertionError($str);
    }

    private function appendProbableCause(\Exception $probableCause = null)
    {
        $str = "";

        if (null !== $probableCause) {
            $str .= PHP_EOL;
            $str .= "A probable cause for the wrong chain of events is an "
                    . "exception that occurred while handling the command.";
            $str .= PHP_EOL;
            $str .= $probableCause->getTraceAsString();
        }

        return $str;
    }

    private function writeStackTrace(\Exception $actualException)
    {
        return $actualException->getTraceAsString();
    }

    private function nullSafeToString($value)
    {
        if (null === $value) {
            return "<null>";
        }
        return $value;
    }

    private function describe($value)
    {
        if (null === $value) {
            return "null";
        } else {
            return $value;
        }
    }

    private function appendEventOverview(array $leftColumnEvents,
            array $rightColumnEvents, $leftColumnName, $rightColumnName)
    {
        $actualTypes = array();
        $expectedTypes = array();
        $largestExpectedSize = strlen($leftColumnName);

        foreach ($rightColumnEvents as $event) {
            $actualTypes[] = $this->payloadContentType($event);
        }

        foreach ($leftColumnEvents as $event) {
            $simpleName = $this->payloadContentType($event);
            if (strlen($simpleName) > $largestExpectedSize) {
                $largestExpectedSize = strlen($simpleName);
            }
            $expectedTypes[] = $simpleName;
        }

        $str = PHP_EOL . PHP_EOL;
        $str .= $leftColumnName . $this->pad(strlen($leftColumnName),
                        $largestExpectedSize, " ");
        $str .= "  |  " . $rightColumnName . PHP_EOL;
        $str .= $this->pad(0, $largestExpectedSize, "-") . "--|--";
        $str .= $this->pad(0, $largestExpectedSize, "-") . PHP_EOL;

        $actualIterator = new \ArrayIterator($actualTypes);
        $expectedIterator = new \ArrayIterator($expectedTypes);

        while ($actualIterator->valid() || $expectedIterator->valid()) {
            $expected = "";
            $difference = false;

            if (null !== $expectedIterator->current()) {
                $expected = $expectedIterator->current();
                $str .= $expected;
                $str .= $this->pad(strlen($expected), $largestExpectedSize, " ");
            } else {
                $str .= $this->pad(0, $largestExpectedSize, " ");
                $difference = true;
            }

            if (null !== $actualIterator->current()) {
                $actual = $actualIterator->current();
                $difference = $difference || !strcmp($expected, $actual) === 0;

                if ($difference) {
                    $str .= " <|> ";
                } else {
                    $str .= "  |  ";
                }

                $str .= $actual;
            } else {
                $str .= " <|> ";
            }

            $str .= PHP_EOL;

            $actualIterator->next();
            $expectedIterator->next();
        }

        return $str;
    }

    private function payloadContentType($event)
    {
        if ($event instanceof EventMessageInterface) {
            return $event->getPayloadType();
        } else {
            return get_class($event);
        }
    }

    private function pad($currentLength, $targetLength, $character)
    {
        $str = "";
        for ($t = $currentLength; $t < $targetLength; $t++) {
            $str .= $character;
        }

        return $str;
    }

}
