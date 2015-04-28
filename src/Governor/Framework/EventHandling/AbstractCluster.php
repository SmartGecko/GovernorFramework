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

/**
 * Description of AbstractCluster
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
abstract class AbstractCluster implements ClusterInterface, LoggerAwareInterface
{

    /**
     * @var string
     */
    private $name;

    /**
     * @var \SplObjectStorage
     */
    private $eventListeners;

    /**
     * @var DefaultClusterMetaData
     */
    private $clusterMetaData;

    /**
     * @var OrderResolverInterface
     */
    private $orderResolver;
    private $subscribedMonitors;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    protected function __construct(
        $name,
        OrderResolverInterface $orderResolver = null
    ) {
        if (null === $name) {
            throw new \InvalidArgumentException("name may not be null");
        }

        $this->name = $name;
        $this->eventListeners = new \SplObjectStorage();
        $this->clusterMetaData = new DefaultClusterMetaData();
        $this->orderResolver = $orderResolver;
        $this->subscribedMonitors = new EventProcessingMonitorCollection();
    }

    protected abstract function doPublish(array $events, array $eventListeners);

    public function getMembers()
    {
        $listeners = array();
        $this->eventListeners->rewind();

        while ($this->eventListeners->valid()) {
            $listeners[] = $this->eventListeners->current();
            $this->eventListeners->next();
        }

        if (null !== $this->orderResolver) {
            uasort(
                $listeners,
                function ($a, $b) {
                    $orderA = $this->orderResolver->orderOf($a);
                    $orderB = $this->orderResolver->orderOf($b);

                    if ($orderA === $orderB) {
                        return 0;
                    }

                    return ($orderA < $orderB) ? -1 : 1;
                }
            );
        }

        return $listeners;
    }

    public function getMetaData()
    {
        return $this->clusterMetaData;
    }

    public function getName()
    {
        return $this->name;
    }

    public function publish(array $events)
    {
        $this->doPublish($events, $this->getMembers());
    }

    public function subscribe(EventListenerInterface $eventListener)
    {
        if (!$this->eventListeners->contains($eventListener)) {
            $this->eventListeners->attach($eventListener);
        }
    }

    public function unsubscribe(EventListenerInterface $eventListener)
    {
        if ($this->eventListeners->contains($eventListener)) {
            $this->eventListeners->detach($eventListener);
        }
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    protected function getClassName(EventListenerInterface $eventListener)
    {
        if ($eventListener instanceof EventListenerProxyInterface) {
            $listenerType = $eventListener->getTargetType();
        } else {
            $listenerType = get_class($eventListener);
        }

        return $listenerType;
    }

    public function subscribeEventProcessingMonitor(EventProcessingMonitorInterface $monitor)
    {
        $this->subscribedMonitors->subscribeEventProcessingMonitor($monitor);
    }

    public function unsubscribeEventProcessingMonitor(EventProcessingMonitorInterface $monitor)
    {
        $this->subscribedMonitors->unsubscribeEventProcessingMonitor($monitor);
    }

}
