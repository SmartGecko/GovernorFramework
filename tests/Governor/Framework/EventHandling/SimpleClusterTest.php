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

use Governor\Framework\Domain\GenericEventMessage;

/**
 * Description of SimpleClusterTest
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a> 
 */
class SimpleClusterTest extends \PHPUnit_Framework_TestCase
{

    private $testSubject;
    private $eventListener;

    public function setUp()
    {
        $this->testSubject = new SimpleCluster("cluster");
        $this->testSubject->setLogger($this->getMock(\Psr\Log\LoggerInterface::class));
        $this->eventListener = $this->getMock(EventListenerInterface::class);
    }

    public function testSubscribeMember()
    {
        $this->testSubject->subscribe($this->eventListener);
        $this->assertCount(1, $this->testSubject->getMembers());
    }

    public function testSubscribeOrderedMembers()
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

    public function testUnsubscribeMember()
    {
        $this->assertCount(0, $this->testSubject->getMembers());
        $this->testSubject->subscribe($this->eventListener);
        $this->testSubject->unsubscribe($this->eventListener);
        $this->assertCount(0, $this->testSubject->getMembers());
    }

    public function testMetaDataAvailable()
    {
        $this->assertNotNull($this->testSubject->getMetaData());
    }

    public function testPublishEvent()
    {
        $this->testSubject->subscribe($this->eventListener);

        $this->eventListener->expects($this->once())
                ->method('handle');

        $event = new GenericEventMessage(new \stdClass());
        $this->testSubject->publish(array($event));
    }

}
