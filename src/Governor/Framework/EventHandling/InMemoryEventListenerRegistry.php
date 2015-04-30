<?php
/**
 * This file is part of the SmartGecko(c) business platform.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Governor\Framework\EventHandling;


class InMemoryEventListenerRegistry implements EventListenerRegistryInterface
{
    /**
     * @var \SplObjectStorage
     */
    private $listeners;

    function __construct()
    {
        $this->listeners = new \SplObjectStorage();
    }


    /**
     * Subscribe the given <code>eventListener</code> to this bus. When subscribed, it will receive all events
     * published to this bus.
     * <p/>
     * If the given <code>eventListener</code> is already subscribed, nothing happens.
     *
     * @param EventListenerInterface $eventListener The event listener to subscribe
     * @throws EventListenerSubscriptionFailedException if the listener could not be subscribed
     */
    public function subscribe(EventListenerInterface $eventListener)
    {
        if (!$this->listeners->contains($eventListener)) {
            $this->listeners->attach($eventListener);
        }
    }

    /**
     * Unsubscribe the given <code>eventListener</code> to this bus. When unsubscribed, it will no longer receive
     * events published to this bus.
     *
     * @param EventListenerInterface $eventListener The event listener to unsubscribe
     */
    public function unsubscribe(EventListenerInterface $eventListener)
    {
        if ($this->listeners->contains($eventListener)) {
            $this->listeners->detach($eventListener);
        }
    }

    /**
     * Returns an array of registered EventListenerInterface-s.
     *
     * @return \SplObjectStorage|EventListenerInterface[]
     */
    public function getListeners()
    {
        return $this->listeners;
    }

    /**
     * @param EventListenerInterface $eventListener
     * @return string
     */
    public function getListenerClassName(EventListenerInterface $eventListener)
    {
        if ($eventListener instanceof EventListenerProxyInterface) {
            $listenerType = $eventListener->getTargetType();
        } else {
            $listenerType = get_class($eventListener);
        }

        return $listenerType;
    }

}