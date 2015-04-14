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

/**
 * Interface providing an API to methods in the "when" state of the fixture execution. Unlike the methods in the
 * "given" state, these methods record the behavior of the Sagas involved for validation.
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
interface WhenStateInterface
{
    /**
     * Use this method to indicate that an aggregate with given identifier should publish certain events, <em>while
     * recording the outcome</em>. In contrast to the {@link FixtureConfiguration#givenAggregate(Object)} given} and
     * {@link org.axonframework.test.saga.ContinuedGivenState#andThenAggregate(Object)} andThen} methods, this method
     * will start recording activity on the EventBus and CommandBus.
     * <p/>
     * Can be chained to build natural sentences:<br/> <code>whenAggregate(someIdentifier).publishes(anEvent)</code>
     * <p/>
     * Note that if you inject resources using {@link FixtureConfiguration#registerResource(Object)}, you may need to
     * reset them yourself if they are manipulated by the Saga in the "given" stage of the test.
     *
     * @param mixed $aggregateIdentifier The identifier of the aggregate the events should appear to come from
     * @return WhenAggregateEventPublisherInterface an object that allows registration of the actual events to send
     */
    public function whenAggregate($aggregateIdentifier);

    /**
     * Use this method to indicate an application is published, <em>while recording the outcome</em>.
     * <p/>
     * Note that if you inject resources using {@link FixtureConfiguration#registerResource(Object)}, you may need to
     * reset them yourself if they are manipulated by the Saga in the "given" stage of the test.
     *
     * @param mixed $event the event to publish
     * @return FixtureExecutionResultInterface an object allowing you to verify the test results
     */
    public function whenPublishingA($event);

    /**
     * Mimic an elapsed time with no relevant activity for the Saga. If any Events are scheduled to be published within
     * this time frame, they are published. All activity by the Saga on the CommandBus and EventBus (meaning that
     * scheduled events are excluded) is recorded.
     * <p/>
     * Note that if you inject resources using {@link FixtureConfiguration#registerResource(Object)}, you may need to
     * reset them yourself if they are manipulated by the Saga in the "given" stage of the test.
     *
     * @param \DateInterval $elapsedTime The amount of time to elapse
     * @return FixtureExecutionResultInterface an object allowing you to verify the test results
     */
    public function whenTimeElapses(\DateInterval $elapsedTime);

    /**
     * Mimic an elapsed time with no relevant activity for the Saga. If any Events are scheduled to be published within
     * this time frame, they are published. All activity by the Saga on the CommandBus and EventBus (meaning that
     * scheduled events are excluded) is recorded.
     * <p/>
     * Note that if you inject resources using {@link FixtureConfiguration#registerResource(Object)}, you may need to
     * reset them yourself if they are manipulated by the Saga in the "given" stage of the test.
     *
     * @param \DateTime $newDateTime The time to advance the clock to
     * @return FixtureExecutionResultInterface an object allowing you to verify the test results
     */
    public function whenTimeAdvancesTo(\DateTime $newDateTime);
}