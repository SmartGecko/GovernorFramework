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

use Governor\Framework\Domain\EventMessageInterface;

/**
 * Description of DefaultClusterTerminal
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
class DefaultClusterTerminal implements ClusterTerminalInterface
{

    /**
     * @var \SplObjectStorage|EventBusInterface[]
     */
    private $eventBuses;

    function __construct()
    {
        $this->eventBuses = new \SplObjectStorage();
    }


    /**
     * @param EventMessageInterface[] $events
     */
    public function publish(array $events)
    {
        foreach ($this->eventBuses as $eventBus) {
            $eventBus->publish($events);
        }
    }

    /**
     * Called on a EventBusInterface has been subscribed.
     *
     * @param EventBusInterface $eventBus
     */
    public function onEventBusSubscribed(EventBusInterface $eventBus)
    {
        if (!$this->eventBuses->contains($eventBus)) {
            $this->eventBuses->attach($eventBus);
        }
    }

    /**
     * Called on a EventBusInterface has been unsubscribed.
     *
     * @param EventBusInterface $eventBus
     */
    public function onEventBusUnsubscribed(EventBusInterface $eventBus)
    {
        if ($this->eventBuses->contains($eventBus)) {
            $this->eventBuses->detach($eventBus);
        }
    }

    /**
     * @return \SplObjectStorage|EventBusInterface[]
     */
    public function getMembers()
    {
        return $this->eventBuses;
    }

}
