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

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareInterface;
use Governor\Framework\Domain\EventMessageInterface;
use Governor\Framework\Common\Logging\NullLogger;

/**
 * Simple in memory event bus implementation.
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
class SimpleEventBus implements EventBusInterface, LoggerAwareInterface
{


    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var EventListenerRegistryInterface
     */
    private $eventListenerRegistry;

    /**
     * @var TerminalInterface[]
     */
    private $terminals;

    /**
     * @param EventListenerRegistryInterface $eventListenerRegistry
     */
    function __construct(EventListenerRegistryInterface $eventListenerRegistry)
    {
        $this->eventListenerRegistry = $eventListenerRegistry;
        $this->logger = new NullLogger();
        $this->terminals = [];
    }

    /**
     * @param EventMessageInterface[] $events
     */
    public function publish(array $events)
    {
        $listeners = $this->eventListenerRegistry->getListeners();

        foreach ($events as $event) {
            $listeners->rewind();

            while ($listeners->valid()) {
                /** @var EventListenerInterface $listener */
                $listener = $listeners->current();

                $this->logger->debug(
                    "Dispatching Event {event} to EventListener {listener}",
                    [
                        "event" => $event->getPayloadType(),
                        "listener" => $this->eventListenerRegistry->getListenerClassName($listener)
                    ]
                );
                $listener->handle($event);

                $listeners->next();
            }
        }

        foreach ($this->terminals as $terminal) {
            $terminal->publish($events);
        }
    }

    /**
     * @return EventListenerRegistryInterface
     */
    public function getEventListenerRegistry()
    {
        return $this->eventListenerRegistry;
    }


    /**
     * {@inheritdoc}
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }


    /**
     * Sets the terminals to which events will be forwarded.
     *
     * @param TerminalInterface[] $terminals
     */
    public function setTerminals(array $terminals)
    {
        $this->terminals = $terminals;
    }
}
