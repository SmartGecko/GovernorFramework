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
 * Utility class containing static methods to obtain instances of (List) Matchers.
 * 
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a> 
 */
abstract class Matchers
{

    /**
     * Matches a list of Messages if a list containing their respective payloads matches the given
     * <code>matcher</code>.
     *
     * @param Matcher $matcher The mather to match against the Message payloads
     * @return Matcher that matches against the Message payloads
     */
    public static function payloadsMatching(Matcher $matcher)
    {
        return new PayloadsMatcher($matcher);
    }

    /**
     * Matches a single Message if the given <code>payloadMatcher</code> matches that message's payload.
     *
     * @param Matcher $payloadMatcher The matcher to match against the Message's payload
     * @return Matcher that evaluates a Message's payload.
     */
    public static function messageWithPayload(Matcher $payloadMatcher)
    {
        return new PayloadMatcher($payloadMatcher);
    }

    /**
     * Matches a List where all the given matchers must match with at least one of the items in that list.
     *
     * @param array $matchers the matchers that should match against one of the items in the List.
     * @return Matcher a matcher that matches a number of matchers against a list
     */
    public static function listWithAllOf(array $matchers)
    {
        return new ListWithAllOfMatcher($matchers);
    }

    /**
     * Matches a List of Events where at least one of the given <code>matchers</code> matches any of the Events in that
     * list.
     *
     * @param array $matchers the matchers that should match against one of the items in the List of Events.
     * @return Matcher a matcher that matches a number of event-matchers against a list of events
     */
    public static function listWithAnyOf(array $matchers)
    {
        return new ListWithAnyOfMatcher($matchers);
    }

    /**
     * Matches a list of Events if each of the <code>matchers</code> match against an Event that comes after the Event
     * that the previous matcher matched against. This means that the given <code>matchers</code> must match in order,
     * but there may be "gaps" of unmatched events in between.
     * <p/>
     * To match the exact sequence of events (i.e. without gaps), use {@link #exactSequenceOf(org.hamcrest.Matcher[])}.
     *
     * @param array $matchers the matchers to match against the list of events
     * @return Matcher a matcher that matches a number of event-matchers against a list of events
     */
    public static function sequenceOf(array $matchers)
    {
        return new SequenceMatcher($matchers);
    }

    /**
     * Matches a List of Events if each of the given <code>matchers</code> matches against the event at the respective
     * index in the list. This means the first matcher must match the first event, the second matcher the second event,
     * and so on.
     * <p/>
     * Any excess Events are ignored. If there are excess Matchers, they will be evaluated against <code>null</code>.
     * To
     * make sure the number of Events matches the number of Matchers, you can append an extra {@link #andNoMore()}
     * matcher.
     * <p/>
     * To allow "gaps" of unmatched Events, use {@link #sequenceOf(org.hamcrest.Matcher[])} instead.
     *
     * @param array $matchers the matchers to match against the list of events
     * @return Matcher a matcher that matches a number of event-matchers against a list of events
     */
    public static function exactSequenceOf(array $matchers)
    {
        return new ExactSequenceMatcher($matchers);
    }

    /**
     * Matches an empty List of Events.
     *
     * @return Matcher a matcher that matches an empty list of events
     */
    public static function noEvents()
    {
        return new EmptyCollectionMatcher("events");
    }

    /**
     * Matches an empty List of Commands.
     *
     * @return Matcher a matcher that matches an empty list of Commands
     */
    public static function noCommands()
    {
        return new EmptyCollectionMatcher("commands");
    }

    /**
     * Matches against each event of the same runtime type that has all field values equal to the fields in the
     * expected
     * event. All fields are compared, except for the aggregate identifier and sequence number, as they are generally
     * not set on the expected event.
     *
     * @param mixed $expected The event with the expected field values     
     * @return Matcher a matcher that matches based on the equality of field values
     */
    public static function equalTo($expected)
    {
        return new EqualFieldsMatcher($expected);
    }

    /**
     * Matches against <code>null</code> or <code>void</code>. Can be used to make sure no trailing events remain when
     * using an Exact Sequence Matcher ({@link #exactSequenceOf(org.hamcrest.Matcher[])}).
     *
     * @return Matcher a matcher that matches against "nothing".
     */
    public static function andNoMore()
    {
        return self::nothing();
    }

    /**
     * Matches against <code>null</code> or <code>void</code>. Can be used to make sure no trailing events remain when
     * using an Exact Sequence Matcher ({@link #exactSequenceOf(org.hamcrest.Matcher[])}).
     *
     * @return Matcher a matcher that matches against "nothing".
     */
    public static function nothing()
    {
        return new NullOrVoidMatcher();
    }

}
