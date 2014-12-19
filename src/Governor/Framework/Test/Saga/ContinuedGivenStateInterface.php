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
 * Interface describing methods that can be executed after the first "given" state has been supplied. Either more
 * "given" state can be appended, or a transition to the definition of "when" can be made.
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
interface ContinuedGivenStateInterface extends WhenStateInterface
{
    /**
     * Use this method to indicate that an aggregate with given identifier published certain events.
     * <p/>
     * Can be chained to build natural sentences:<br/> <code>andThenAggregate(someIdentifier).published(someEvents)
     * </code>
     *
     * @param string $aggregateIdentifier The identifier of the aggregate the events should appear to come from
     * @return GivenAggregateEventPublisherInterface an object that allows registration of the actual events to send
     */
    public function andThenAggregate($aggregateIdentifier);

    /**
     * Simulate time shifts in the current given state. This can be useful when the time between given events is of
     * importance.
     *
     * @param \DateInterval $elapsedTime The amount of time that will elapse
     * @return ContinuedGivenStateInterface an object that allows registration of the actual events to send
     */
    public function andThenTimeElapses(\DateInterval $elapsedTime);

    /**
     * Simulate time shifts in the current given state. This can be useful when the time between given events is of
     * importance.
     *
     * @param \DateTime $newDateTime The time to advance the clock to
     * @return ContinuedGivenStateInterface an object that allows registration of the actual events to send
     */
    public function andThenTimeAdvancesTo(\DateTime $newDateTime);

    /**
     * Indicates that the given <code>event</code> has been published in the past. This event is sent to the associated
     * sagas.
     *
     * @param mixed $event The event to publish
     * @return ContinuedGivenStateInterface an object that allows chaining of more given state
     */
    public function  andThenAPublished($event);
}