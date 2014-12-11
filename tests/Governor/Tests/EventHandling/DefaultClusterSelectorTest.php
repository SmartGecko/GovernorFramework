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

use Governor\Framework\EventHandling\DefaultClusterSelector;
use Governor\Framework\EventHandling\EventListenerInterface;
use Governor\Framework\EventHandling\ClusterInterface;
/**
 * Description of DefaultClusterSelectorTest
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a> 
 */
class DefaultClusterSelectorTest extends \PHPUnit_Framework_TestCase
{

    private $testSubject;

    public function setUp()
    {
        $this->testSubject = new DefaultClusterSelector();
    }

    public function testSameInstanceIsReturned()
    {
        $cluster1 = $this->testSubject->selectCluster($this->getMock(EventListenerInterface::class));
        $cluster2 = $this->testSubject->selectCluster($this->getMock(EventListenerInterface::class));
        $cluster3 = $this->testSubject->selectCluster($this->getMock(EventListenerInterface::class));

        $this->assertSame($cluster1, $cluster2);
        $this->assertSame($cluster2, $cluster3);
    }

    public function testProvidedInstanceIsReturned()
    {
        $mock = $this->getMock(ClusterInterface::class);
        $this->testSubject = new DefaultClusterSelector($mock);
        $this->assertSame($mock,
                $this->testSubject->selectCluster($this->getMock(EventListenerInterface::class)));
    }

}
