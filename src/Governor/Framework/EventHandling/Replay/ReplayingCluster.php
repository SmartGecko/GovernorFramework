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

namespace Governor\Framework\EventHandling\Replay;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareInterface;
use Governor\Framework\EventStore\Management\EventStoreManagementInterface;
use Governor\Framework\EventHandling\ClusterInterface;
use Governor\Framework\EventHandling\EventListenerInterface;

/**
 * Description of ReplayingCluster
 *
 * @author david
 */
class ReplayingCluster implements ClusterInterface, LoggerAwareInterface
{

    /**     
     * @var LoggerInterface
     */
    private $logger;
    
    /**
     * @var ClusterInterface
     */
    private $delegate;
    
    /**     
     * @var EventStoreManagementInterface
     */
    private $replayingEventStore;
    
    //private final int commitThreshold;
    //private final IncomingMessageHandler incomingMessageHandler;
    //private final Set<ReplayAware> replayAwareListeners = new CopyOnWriteArraySet<ReplayAware>();

    //private volatile Status status = Status.LIVE;
    //private final EventProcessingListeners eventHandlingListeners = new EventProcessingListeners();
    
    public function getMembers()
    {
        
    }

    public function getMetaData()
    {
        
    }

    public function getName()
    {
        
    }

    public function publish(array $events)
    {
        
    }

    public function subscribe(EventListenerInterface $eventListener)
    {
        
    }

    public function unsubscribe(EventListenerInterface $eventListener)
    {
        
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

}
