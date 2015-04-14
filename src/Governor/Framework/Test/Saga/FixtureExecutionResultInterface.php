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

namespace Governor\Framework\Test\Saga;

use Hamcrest\Matcher;

interface FixtureExecutionResultInterface
{
    /**
     * Expect the given number of Sagas to be active (i.e. ready to respond to incoming events.
     *
     * @param integer $expected the expected number of active events in the repository
     * @return FixtureExecutionResultInterface the FixtureExecutionResult for method chaining
     */
    public function expectActiveSagas($expected);

    /**
     * Asserts that at least one of the active sagas is associated with the given <code>associationKey</code> and
     * <code>associationValue</code>.
     *
     * @param string $associationKey The key of the association to verify
     * @param string $associationValue The value of the association to verify
     * @return FixtureExecutionResultInterface the FixtureExecutionResult for method chaining
     */
    public function expectAssociationWith($associationKey, $associationValue);

    /**
     * Asserts that at none of the active sagas is associated with the given <code>associationKey</code> and
     * <code>associationValue</code>.
     *
     * @param string $associationKey The key of the association to verify
     * @param string $associationValue The value of the association to verify
     * @return FixtureExecutionResultInterface the FixtureExecutionResult for method chaining
     */
    public function expectNoAssociationWith($associationKey, $associationValue);

    /**
     * Asserts that an event matching the given <code>matcher</code> has been scheduled to be published after the given
     * <code>duration</code>.
     *
     * @param \DateInterval $duration The time to wait before the event should be published
     * @param Matcher $matcher A matcher defining the event expected to be published
     * @return FixtureExecutionResultInterface the FixtureExecutionResult for method chaining
     */
    public function expectScheduledEventMatchingAfter(\DateInterval $duration, Matcher $matcher);

    /**
     * Asserts that an event equal to the given ApplicationEvent has been scheduled for publication after the given
     * <code>duration</code>.
     * <p/>
     * Note that the source attribute of the application event is ignored when comparing events. Events are compared
     * using an "equals" check on all fields in the events.
     *
     * @param \DateInterval $duration The time to wait before the event should be published
     * @param mixed $event The expected event
     * @return FixtureExecutionResultInterface the FixtureExecutionResult for method chaining
     */
    public function expectScheduledEventAfter(\DateInterval $duration, $event);

    /**
     * Asserts that an event of the given <code>eventType</code> has been scheduled for publication after the given
     * <code>duration</code>.
     *
     * @param \DateInterval $duration The time to wait before the event should be published
     * @param string $eventType The type of the expected event
     * @return FixtureExecutionResultInterface the FixtureExecutionResult for method chaining
     */
    public function expectScheduledEventOfTypeAfter(\DateInterval $duration, $eventType);

    /**
     * Asserts that an event matching the given <code>matcher</code> has been scheduled to be published at the given
     * <code>scheduledTime</code>.
     * <p/>
     * If the <code>scheduledTime</code> is calculated based on the "current time", use the {@link
     * org.axonframework.test.saga.FixtureConfiguration#currentTime()} to get the time to use as "current time".
     *
     * @param \DateTime $scheduledTime The time at which the event should be published
     * @param Matcher $matcher A matcher defining the event expected to be published
     * @return FixtureExecutionResultInterface the FixtureExecutionResult for method chaining
     */
    public function expectScheduledEventMatchingOn(\DateTime $scheduledTime, Matcher $matcher);

    /**
     * Asserts that an event equal to the given ApplicationEvent has been scheduled for publication at the given
     * <code>scheduledTime</code>.
     * <p/>
     * If the <code>scheduledTime</code> is calculated based on the "current time", use the {@link
     * org.axonframework.test.saga.FixtureConfiguration#currentTime()} to get the time to use as "current time".
     * <p/>
     * Note that the source attribute of the application event is ignored when comparing events. Events are compared
     * using an "equals" check on all fields in the events.
     *
     * @param \DateTime $scheduledTime The time at which the event should be published
     * @param mixed $event         The expected event
     * @return FixtureExecutionResultInterface the FixtureExecutionResult for method chaining
     */
     public function expectScheduledEventOn(\DateTime $scheduledTime, $event);

    /**
     * Asserts that an event of the given <code>eventType</code> has been scheduled for publication at the given
     * <code>scheduledTime</code>.
     * <p/>
     * If the <code>scheduledTime</code> is calculated based on the "current time", use the {@link
     * org.axonframework.test.saga.FixtureConfiguration#currentTime()} to get the time to use as "current time".
     *
     * @param \DateTime $scheduledTime The time at which the event should be published
     * @param string $eventType     The type of the expected event
     * @return FixtureExecutionResultInterface he FixtureExecutionResult for method chaining
     */
    public function expectScheduledEventOfType(\DateTime $scheduledTime, $eventType);

    /**
     * Asserts that the given commands have been dispatched in exactly the order given. The command objects are
     * compared
     * using the equals method. Only commands as a result of the event in the "when" stage of the fixture are compared.
     *
     * @param array $commands The expected commands
     * @return FixtureExecutionResultInterface the FixtureExecutionResult for method chaining
     */
    public function expectDispatchedCommandsEqualTo(array $commands);

    /**
     * Asserts that the sagas dispatched commands as defined by the given <code>matcher</code>. Only commands as a
     * result of the event in the "when" stage of the fixture are matched.
     *
     * @param Matcher $matcher The matcher that describes the expected list of commands
     * @return FixtureExecutionResultInterface the FixtureExecutionResult for method chaining
     */
    public function expectDispatchedCommandsMatching(Matcher $matcher);

    /**
     * Asserts that the sagas did not dispatch any commands. Only commands as a result of the event in the "when" stage
     * of ths fixture are recorded.
     *
     * @return FixtureExecutionResultInterface the FixtureExecutionResult for method chaining
     */
    public function expectNoDispatchedCommands();

    /**
     * Assert that no events are scheduled for publication. This means that either no events were scheduled at all, all
     * schedules have been cancelled or all scheduled events have been published already.
     *
     * @return FixtureExecutionResultInterface the FixtureExecutionResult for method chaining
     */
    public function  expectNoScheduledEvents();

    /**
     * Assert that the saga published events on the EventBus as defined by the given <code>matcher</code>. Only events
     * published in the "when" stage of the tests are matched.
     *
     * @param Matcher $matcher The matcher that defines the expected list of published events.
     * @return FixtureExecutionResultInterface the FixtureExecutionResult for method chaining
     */
    public function expectPublishedEventsMatching(Matcher $matcher);

    /**
     * Assert that the saga published events on the EventBus in the exact sequence of the given <code>expected</code>
     * events. Events are compared comparing their type and fields using equals. Sequence number, aggregate identifier
     * (for domain events) and source (for application events) are ignored in the comparison.
     *
     * @param array $expected The sequence of events expected to be published by the Saga
     * @return FixtureExecutionResultInterface the FixtureExecutionResult for method chaining
     */
    public function expectPublishedEvents(array $expected);
}