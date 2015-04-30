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
     * @var DefaultClusterMetaData
     */
    private $clusterMetaData;

    /**
     * @var OrderResolverInterface
     */
    private $orderResolver;

    /**
     * @var EventProcessingMonitorCollection
     */
    private $subscribedMonitors;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ClusterTerminalInterface
     */
    private $clusterTerminal;

    /**
     * @param string $name
     * @param ClusterTerminalInterface $clusterTerminal
     * @param OrderResolverInterface $orderResolver
     */
    protected function __construct(
        $name,
        ClusterTerminalInterface $clusterTerminal,
        OrderResolverInterface $orderResolver = null
    ) {
        if (null === $name) {
            throw new \InvalidArgumentException("Cluster name cannot not be null.");
        }

        $this->name = $name;
        $this->clusterTerminal = $clusterTerminal;
        $this->clusterMetaData = new DefaultClusterMetaData();
        $this->orderResolver = $orderResolver;
        $this->subscribedMonitors = new EventProcessingMonitorCollection();
    }

    /*
      public function getMembers()
      {
          $result = [];
          $listeners = $this->eventListenerRegistry->getListeners();
          $listeners->rewind();

          while ($listeners->valid()) {
              $result[] = $listeners->current();
              $listeners->next();
          }

          if (null !== $this->orderResolver) {
              uasort(
                  $result,
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

          return $result;
      }*/
    /**
     * @return EventBusInterface[]
     */
    public function getMembers()
    {
        return $this->clusterTerminal->getMembers();
    }


    /**
     * @return ClusterMetaDataInterface
     */
    public function getMetaData()
    {
        return $this->clusterMetaData;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param EventMessageInterface[] $events
     */
    public function publish(array $events)
    {
        $this->clusterTerminal->publish($events);
    }

    /**
     * Subscribes the EventBusInterface to this cluster.
     *
     * @param EventBusInterface $eventBus
     */
    public function subscribe(EventBusInterface $eventBus)
    {
        $this->clusterTerminal->onEventBusSubscribed($eventBus);
    }

    /**
     * Unsubscribes the EventBusInterface from this cluster.
     *
     * @param EventBusInterface $eventBus
     */
    public function unsubscribe(EventBusInterface $eventBus)
    {
        $this->clusterTerminal->onEventBusUnsubscribed($eventBus);
    }

    /**
     * @param LoggerInterface $logger
     * @return null
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param EventProcessingMonitorInterface $monitor
     */
    public function subscribeEventProcessingMonitor(EventProcessingMonitorInterface $monitor)
    {
        $this->subscribedMonitors->subscribeEventProcessingMonitor($monitor);
    }

    /**
     * @param EventProcessingMonitorInterface $monitor
     */
    public function unsubscribeEventProcessingMonitor(EventProcessingMonitorInterface $monitor)
    {
        $this->subscribedMonitors->unsubscribeEventProcessingMonitor($monitor);
    }

}
