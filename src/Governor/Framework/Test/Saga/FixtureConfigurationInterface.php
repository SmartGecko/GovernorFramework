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

use Governor\Framework\CommandHandling\Gateway\CommandGatewayInterface;

/**
 * Interface describing action to perform on a Saga Test Fixture during the configuration phase.
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
interface FixtureConfigurationInterface
{
    /**
     * Registers the given <code>resource</code>. When a Saga is created, all resources are injected on that instance
     * before any Events are passed onto it.
     * <p/>
     * Note that a CommandBus, EventBus and EventScheduler are already registered as resources, and need not be
     * registered again.
     * <p/>
     * Also note that you might need to reset the resources manually if you want to isolate behavior during the "when"
     * stage of the test.
     *
     * @param string $id resource identifier
     * @param mixed $resource the resource to register.
     */
    public function registerResource($id, $resource);

    /**
     * Creates a Command Gateway for the given <code>gatewayInterface</code> and registers that as a resource. The
     * gateway will dispatch commands on the Command Bus contained in this Fixture, so that you can validate commands
     * using {@link FixtureExecutionResult#expectDispatchedCommandsEqualTo(Object...)} and {@link
     * FixtureExecutionResult#expectDispatchedCommandsMatching(org.hamcrest.Matcher)}.
     * <p/>
     * Note that you need to use {@link #setCallbackBehavior(org.axonframework.test.utils.CallbackBehavior)} to defined
     * the behavior of commands when expecting return values. Alternatively, you can use {@link
     * #registerCommandGateway(Class, Object)} to define behavior using a stub implementation.
     *
     * @param CommandGatewayInterface $gatewayInterface The interface describing the gateway
     * @return CommandGatewayInterface the gateway implementation being registered as a resource.
     */
    public function registerCommandGateway(CommandGatewayInterface $gatewayInterface);


    /*
     * Sets the instance that defines the behavior of the Command Bus when a command is dispatched with a callback.
     *
     * @param callbackBehavior The instance deciding to how the callback should be invoked.
     */
    //void setCallbackBehavior(CallbackBehavior callbackBehavior);

    /**
     * Use this method to indicate that an aggregate with given identifier published certain events.
     * <p/>
     * Can be chained to build natural sentences:<br/>
     * <code>andThenAggregate(someIdentifier).published(someEvents)</code>
     *
     * @param string $aggregateIdentifier The identifier of the aggregate the events should appear to come from
     * @return GivenAggregateEventPublisherInterface an object that allows registration of the actual events to send
     */
    public function givenAggregate($aggregateIdentifier);

    /**
     * Indicates that the given <code>applicationEvent</code> has been published in the past. This event is sent to the
     * associated sagas.
     *
     * @param mixed $event The event to publish
     * @return ContinuedGivenStateInterface an object that allows chaining of more given state
     */
    public function givenAPublished($event);

    /**
     * Indicates that no relevant activity has occurred in the past.
     *
     * @return WhenStateInterface an object that allows the definition of the activity to measure Saga behavior
     */
    public function givenNoPriorActivity();

    /**
     * Returns the time as "known" by the fixture. This is the time at which the fixture was created, plus the amount
     * of
     * time the fixture was told to simulate a "wait".
     * <p/>
     * This time can be used to predict calculations that the saga may have made based on timestamps from the events it
     * received.
     *
     * @return \DateTime the simulated "current time" of the fixture.
     */
    public function currentTime();
}