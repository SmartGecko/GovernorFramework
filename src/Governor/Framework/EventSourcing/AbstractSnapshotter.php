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

namespace Governor\Framework\EventSourcing;

use Governor\Framework\Common\Logging\NullLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareInterface;
use Governor\Framework\Domain\DomainEventStreamInterface;
use Governor\Framework\Domain\DomainEventMessageInterface;
use Governor\Framework\EventStore\SnapshotEventStoreInterface;

/**
 * Abstract implementation of the {@link SnapshotterInterface}.
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a> 
 */
abstract class AbstractSnapshotter implements SnapshotterInterface, LoggerAwareInterface
{
    /**     
     * @var SnapshotEventStoreInterface
     */
    private $eventStore;
    
    /**     
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param SnapshotEventStoreInterface $eventStore
     */
    public function __construct(SnapshotEventStoreInterface $eventStore)
    {
        $this->eventStore = $eventStore;
        $this->logger = new NullLogger();
    }

    
    /**
     * {@inheritDoc}
     */
    public function scheduleSnapshot($typeIdentifier, $aggregateIdentifier) 
    {
        
    }
    
     /**
     * Creates a snapshot event for an aggregate of the given <code>typeIdentifier</code> of which passed events are
     * available in the given <code>eventStream</code>. May return <code>null</code> to indicate a snapshot event is
     * not necessary or appropriate for the given event stream.
     *
     * @param string $typeIdentifier      The aggregate's type identifier
     * @param mixed $aggregateIdentifier The identifier of the aggregate to create a snapshot for
     * @param DomainEventStreamInterface $eventStream         The event stream containing the aggregate's past events
     * @return DomainEventMessageInterface the snapshot event for the given events, or <code>null</code> if none should be stored.
     */
    protected abstract function createSnapshot($typeIdentifier, $aggregateIdentifier,
                                                         DomainEventStreamInterface $eventStream);


    /**
     * @param LoggerInterface $logger
     * @return void
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

}
