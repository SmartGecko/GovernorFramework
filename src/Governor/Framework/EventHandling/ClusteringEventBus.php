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
 * Description of ClusteringEventBus
 * 
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a> 
 */
class ClusteringEventBus implements EventBusInterface, LoggerAwareInterface
{
    
    /**     
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var EventBusTerminalInterface
     */
    private $terminal;

    /**
     * @var ClusterSelectorInterface
     */
    private $clusterSelector;
    private $clusters = array();

    /**
     * Initializes a <code>ClusteringEventBus</code> with the given <code>clusterSelector</code> and a
     * <code>terminal</code>.
     *
     * @param ClusterSelectorInterface $clusterSelector The Cluster Selector that chooses the cluster for each of the subscribed event listeners
     * @param EventBusTerminalInterface $terminal        The terminal responsible for publishing incoming events to each of the clusters
     */
    public function __construct(ClusterSelectorInterface $clusterSelector = null,
            EventBusTerminalInterface $terminal = null)
    {
        $this->clusterSelector = (null === $clusterSelector) ? new DefaultClusterSelector()
                    : $clusterSelector;
        $this->terminal = (null === $terminal) ? new SimpleEventBusTerminal() : $terminal;
    }

    public function publish(array $events)
    {        
        $this->terminal->publish($events);
    }

    public function subscribe(EventListenerInterface $eventListener)
    {
        $this->clusterFor($eventListener)->subscribe($eventListener);
    }

    public function unsubscribe(EventListenerInterface $eventListener)
    {
        $this->clusterFor($eventListener)->unsubscribe($eventListener);
    }        
    
    public function getCluster()
    {
        return current($this->clusters); /// !!! TODO motherfucking ugly hack
    }

    private function clusterFor(EventListenerInterface $eventListener)
    {
        $cluster = $this->clusterSelector->selectCluster($eventListener);

        if (null === $cluster) {
            $listenerType = get_class($eventListener);

            if ($eventListener instanceof EventListenerProxyInterface) {
                $listenerType = $eventListener->getTargetType();
            }

            throw new EventListenerSubscriptionFailedException(sprintf(
                    "Unable to subscribe [%s] to the Event Bus. There is no suitable cluster for it. " .
                    "Make sure the ClusterSelector is configured properly",
                    $listenerType));
        }

        if (!in_array($cluster, $this->clusters, true)) {
            $cluster->setLogger($this->logger);
            $this->clusters[] = $cluster;
            $this->terminal->onClusterCreated($cluster);
        }

        return $cluster;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

}
