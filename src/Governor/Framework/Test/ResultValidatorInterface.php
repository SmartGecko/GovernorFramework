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

/**
 * Interface describing the operations available on the "validate result" (a.k.a. "then") stage of the test execution.
 * The underlying fixture expects a test to have been executed succesfully using a {@link
 * org.axonframework.test.TestExecutor}.
 * <p/>
 * There are several things to validate:<ul><li>the published events,<li>the stored events, and<li>the command
 * handler's
 * execution result, which is one of <ul><li>a regular return value,<li>a <code>void</code> return value, or<li>an
 * exception.</ul></ul>
 *
 * @author Allard Buijze
 * @since 0.6
 */
interface ResultValidatorInterface
{

    /**
     * Expect the given set of events to have been stored and published. Note that this assertion will fail if events
     * were published but not saved. If you expect events (e.g. Application Events) to have been dispatched, use the
     * {@link #expectPublishedEvents(Object...)} and {@link #expectStoredEvents(Object...)} methods instead.
     * <p/>
     * All events are compared for equality using a shallow equals comparison on all the fields of the events. This
     * means that all assigned values on the events' fields should have a proper equals implementation.
     * <p/>
     * Note that the event identifier is ignored in the comparison.
     *
     * @param array $expectedEvents The expected events, in the exact order they are expected to be dispatched and stored.
     * @return ResultValidatorInterface the current ResultValidator, for fluent interfacing
     */
    public function expectEvents(array $expectedEvents);

    /**
     * Expect the published events to match the given <code>matcher</code>. Note that this assertion will fail if
     * events
     * were published but not saved.
     * <p/>
     * Note: if no events were published or stored, the matcher receives an empty List.
     *
     * @param Matcher $matcher The matcher to match with the actually published events
     * @return ResultValidatorInterface the current ResultValidator, for fluent interfacing
     */
    public function expectEventsMatching(Matcher $matcher);

    /**
     * Expect the given set of events to have been published on the events bus. If you expect the same events to be
     * stored, too, consider using the {@link #expectEvents(Object...)} instead.
     * <p/>
     * All events are compared for equality using a shallow equals comparison on all the fields of the events. This
     * means that all assigned values on the events' fields should have a proper equals implementation.
     * <p/>
     * Note that the event identifier is ignored in the comparison. For Application and System events, however, the
     * <code>source</code> of the events must be equal, too.
     *
     * @param array $expectedEvents The expected events, in the exact order they are expected to be dispatched.
     * @return ResultValidatorInterface the current ResultValidator, for fluent interfacing
     */
    public function expectPublishedEvents(array $expectedEvents);

    /**
     * Expect the list of published event to match the given <code>matcher</code>. This method will only take into
     * account the events that have been published. Stored events that have not been published to the event bus are
     * ignored.
     * <p/>
     * Note: if no events were published, the matcher receives an empty List.
     *
     * @param Matcher $matcher The matcher which validates the actual list of published events.
     * @return ResultValidatorInterface the current ResultValidator, for fluent interfacing
     */
    public function expectPublishedEventsMatching(Matcher $matcher);

    /**
     * Expect the given set of events to have been stored in the event store. If you expect the same events to be
     * published, too, consider using the {@link #expectEvents(Object...)} instead.
     * <p/>
     * All events are compared for equality using a shallow equals comparison on all the fields of the events. This
     * means that all assigned values on the events' fields should have a proper equals implementation.
     * <p/>
     * Note that the event identifier is ignored in the comparison. For Application and System events, however, the
     * <code>source</code> of the events must be equal, too.
     *
     * @param array $expectedEvents The expected events, in the exact order they are expected to be stored.
     * @return ResultValidatorInterface the current ResultValidator, for fluent interfacing
     */
    public function expectStoredEvents(array $expectedEvents);

    /**
     * Expect the list of stored event to match the given <code>matcher</code>. This method will only take into account
     * the events that have been stored. Stored events that have not been stored in the event store are ignored.
     * <p/>
     * Note: if no events were stored, the matcher receives an empty List.
     *
     * @param Matcher $matcher The matcher which validates the actual list of stored events.
     * @return ResultValidatorInterface the current ResultValidator, for fluent interfacing
     */
    public function expectStoredEventsMatching(Matcher $matcher);

    /**
     * Expect the command handler to return a value that matches the given <code>matcher</code> after execution.
     *
     * @param Matcher $matcher The matcher to verify the actual return value against
     * @return ResultValidatorInterface the current ResultValidator, for fluent interfacing
     */
    public function expectReturnValue(Matcher $matcher);

    /**
     * Expect the given <code>expectedException</code> to occur during command handler execution. The actual exception
     * should be exactly of that type, subclasses are not accepted.
     *
     * @param Matcher $matcher The type of exception expected from the command handler execution
     * @return ResultValidatorInterface the current ResultValidator, for fluent interfacing
     */
    public function expectException(Matcher $matcher);

    /**
     * Explicitly expect a <code>void</code> return type on the given command handler. <code>void</code> is the
     * recommended return value for all command handlers as they allow for a more scalable architecture.
     *
     * @return ResultValidatorInterface the current ResultValidator, for fluent interfacing
     */
    public function expectVoidReturnType();
}
