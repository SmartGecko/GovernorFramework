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
use Governor\Framework\Domain\GenericEventMessage;

/**
 * Description of ClusteringEventBusTest
 *
 * @author david
 */
class ClusteringEventBusTest extends \PHPUnit_Framework_TestCase
{

    private $eventBus;

    public function setUp()
    {
        $this->eventBus = new ClusteringEventBus();
        $this->eventBus->setLogger($this->getMock(\Psr\Log\LoggerInterface::class));
    }

    public function testEventIsPublishedToAllClustersWithDefaultConfiguration()
    {
        $listener1 = new RecordingClusteredEventListener("cluster1");
        $listener2 = new RecordingClusteredEventListener("cluster2");
        $this->eventBus->subscribe($listener1);
        $this->eventBus->subscribe($listener2);

        $this->eventBus->publish(array(new GenericEventMessage(new \stdClass())));

        $this->assertCount(1, $listener1->getReceivedEvents());
        $this->assertCount(1, $listener2->getReceivedEvents());
    }

    public function testEventSentToTerminal()
    {
        $mockTerminal = \Phake::mock(EventBusTerminalInterface::class);
        $this->eventBus = new ClusteringEventBus(null, $mockTerminal);
        $this->eventBus->setLogger($this->getMock(\Psr\Log\LoggerInterface::class));

        $mockEventListener = \Phake::mock(EventListenerInterface::class);

        $this->eventBus->subscribe($mockEventListener);

        $this->eventBus->publish(array(new GenericEventMessage(new \stdClass())));

        \Phake::verify($mockTerminal, \Phake::times(1))->publish(\Phake::anyParameters());
        \Phake::verify($mockEventListener, \Phake::never())->handle(\Phake::anyParameters());
    }

}

class RecordingClusteredEventListener implements EventListenerInterface
{

    private $receivedEvents = array();
    private $clusterName;

    public function __construct($clusterName)
    {
        $this->clusterName = $clusterName;
    }

    public function handle(EventMessageInterface $event)
    {
        $this->receivedEvents[] = $event;
    }

    public function getReceivedEvents()
    {
        return $this->receivedEvents;
    }

}
