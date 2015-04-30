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

namespace Governor\Framework\EventHandling;

/**
 * Manages a registry of available event listeners.
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
interface EventListenerRegistryInterface
{
    /**
     * Subscribe the given <code>eventListener</code> to this bus. When subscribed, it will receive all events
     * published to this bus.
     * <p/>
     * If the given <code>eventListener</code> is already subscribed, nothing happens.
     *
     * @param EventListenerInterface $eventListener The event listener to subscribe
     * @throws EventListenerSubscriptionFailedException if the listener could not be subscribed
     */
    public function subscribe(EventListenerInterface $eventListener);

    /**
     * Unsubscribe the given <code>eventListener</code> to this bus. When unsubscribed, it will no longer receive
     * events published to this bus.
     *
     * @param EventListenerInterface $eventListener The event listener to unsubscribe
     */
    public function unsubscribe(EventListenerInterface $eventListener);

    /**
     * Returns an array of registered EventListenerInterface-s.
     *
     * @return \SplObjectStorage|EventListenerInterface[]
     */
    public function getListeners();

    /**
     * Returns the class name of the EventListenerInterface.
     *
     * @param EventListenerInterface $eventListener
     * @return string
     */
    public function getListenerClassName(EventListenerInterface $eventListener);
}