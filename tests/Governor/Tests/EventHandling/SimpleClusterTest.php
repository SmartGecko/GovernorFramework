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

namespace Governor\Tests\EventHandling;

use Governor\Framework\Domain\GenericEventMessage;
use Governor\Framework\EventHandling\InMemoryEventListenerRegistry;
use Governor\Framework\EventHandling\SimpleCluster;
use Governor\Framework\EventHandling\EventListenerInterface;
use Governor\Framework\EventHandling\OrderResolverInterface;
use Governor\Framework\EventHandling\SimpleEventBus;

/**
 * SimpleCluster unit tests
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
class SimpleClusterTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var SimpleCluster
     */
    private $testSubject;

    /**
     * @var EventListenerInterface
     */
    private $eventListener;

    /**
     * @var SimpleEventBus
     */
    private $eventBus;

    public function setUp()
    {
        $this->testSubject = new SimpleCluster("cluster");
        $this->eventListener = $this->getMock(EventListenerInterface::class);
        $this->eventBus = new SimpleEventBus(new InMemoryEventListenerRegistry());
    }

    public function testSubscribeEventBus()
    {
        $this->testSubject->subscribe($this->eventBus);
        $this->assertCount(1, $this->testSubject->getMembers());
    }

    public function testUnsubscribeEventBus()
    {
        $this->assertCount(0, $this->testSubject->getMembers());
        $this->testSubject->subscribe($this->eventBus);
        $this->testSubject->unsubscribe($this->eventBus);
        $this->assertCount(0, $this->testSubject->getMembers());
    }

    public function testMetaDataAvailable()
    {
        $this->assertNotNull($this->testSubject->getMetaData());
    }

    public function testPublishEvent()
    {
        $this->testSubject->subscribe($this->eventBus);
        $this->eventBus->getEventListenerRegistry()->subscribe($this->eventListener);

        $this->eventListener->expects($this->once())
            ->method('handle');

        $this->testSubject->publish([GenericEventMessage::asEventMessage(new \stdClass())]);
    }


    /*    public function testSubscribeOrderedMembers()
        {
            $mockOrderResolver = $this->getMock(OrderResolverInterface::class);
            $eventListener2 = $this->getMock(EventListenerInterface::class);

            $mockOrderResolver->expects($this->any())
                    ->method('orderOf')
                    ->with($this->eventListener)
                    ->will($this->returnValue(1));

            $mockOrderResolver->expects($this->any())
                    ->method('orderOf')
                    ->with($eventListener2)
                    ->will($this->returnValue(2));

            $this->testSubject = new SimpleCluster("cluster", $mockOrderResolver);
            $this->testSubject->subscribe($eventListener2);
            $this->testSubject->subscribe($this->eventListener);
            $this->assertCount(2, $this->testSubject->getMembers());
            // the eventListener instance must come first
            $this->assertSame($this->eventListener,
                    current($this->testSubject->getMembers()));
        }

    */
}
