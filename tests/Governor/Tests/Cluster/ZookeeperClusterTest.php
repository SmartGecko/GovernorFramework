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

namespace Governor\Tests\Cluster;

use Governor\Framework\Cluster\ClusterException;
use Governor\Framework\Cluster\AbstractClusterNode;
use Governor\Framework\Cluster\ClusterInterface;
use Governor\Framework\Cluster\ZookeeperCluster;

class ZookeeperClusterTest extends \PHPUnit_Framework_TestCase
{
    const TEST_CLUSTER_NAME = 'testCluster';

    const TEST_CLUSTER_LISTENERS = '/governor/testCluster/listeners';
    const TEST_CLUSTER_HANDLERS = '/governor/testCluster/handlers';
    const TEST_CLUSTER_NODES = '/governor/testCluster/nodes';
    /**
     * @var ZookeeperCluster
     */
    private $subject;

    public function setUp()
    {
        $this->subject = new ZookeeperCluster('localhost:2181');
    }

    public function tearDown()
    {
        try {
            $this->subject->drop(self::TEST_CLUSTER_NAME);
        } catch (ClusterException $e) {
            //ignore
        }
    }

    public function testCreateCluster()
    {
        $this->subject->create(self::TEST_CLUSTER_NAME);
        $this->assertEquals(self::TEST_CLUSTER_NAME, $this->subject->getZookeeper()->get('/governor/testCluster'));

        $this->assertContains(
            ZookeeperCluster::LOCKS_NODE,
            $this->subject->getZookeeper()->getChildren('/governor/testCluster')
        );
        $this->assertContains(
            ZookeeperCluster::LISTENERS_NODE,
            $this->subject->getZookeeper()->getChildren('/governor/testCluster')
        );
        $this->assertContains(
            ZookeeperCluster::NODES_NODE,
            $this->subject->getZookeeper()->getChildren('/governor/testCluster')
        );

        $this->assertInstanceOf(\Zookeeper::class, $this->subject->getZookeeper());
        $this->assertEquals(self::TEST_CLUSTER_NAME, $this->subject->getClusterIdentifier());
    }

    public function testConnectCluster()
    {
        $this->subject->create(self::TEST_CLUSTER_NAME);
        $this->subject->connect(self::TEST_CLUSTER_NAME);
        $this->assertEquals(self::TEST_CLUSTER_NAME, $this->subject->getClusterIdentifier());
    }

    public function testDropCluster()
    {
        $this->subject->create(self::TEST_CLUSTER_NAME);
        $this->subject->connect(self::TEST_CLUSTER_NAME);
        $this->assertEquals(self::TEST_CLUSTER_NAME, $this->subject->getClusterIdentifier());

        $this->subject->drop(self::TEST_CLUSTER_NAME);
        $this->assertNull($this->subject->getClusterIdentifier());
        $this->assertFalse($this->subject->getZookeeper()->exists('/governor/testCluster'));
    }

    public function testMessageRegistry()
    {
        $this->subject->create(self::TEST_CLUSTER_NAME);

        $registry = $this->subject->createMessageRegistry('handlers');

        $this->assertContains(
           'handlers',
            $this->subject->getZookeeper()->getChildren('/governor/testCluster')
        );
    }


    public function testRegisterNode()
    {
        $this->subject->create(self::TEST_CLUSTER_NAME);
        $this->subject->connect(self::TEST_CLUSTER_NAME);

        $node = new TestNode('node');
        $this->subject->registerNode($node);

        $this->assertCount(1, $this->subject->getZookeeper()->getChildren(self::TEST_CLUSTER_NODES));
        $this->assertContains(
            $node->getNodeIdentifier().'-0000000000',
            $this->subject->getZookeeper()->getChildren(self::TEST_CLUSTER_NODES)
        );

        $this->subject->registerNode($node);
        $this->assertCount(2, $this->subject->getZookeeper()->getChildren(self::TEST_CLUSTER_NODES));
        $this->assertContains(
            $node->getNodeIdentifier().'-0000000001',
            $this->subject->getZookeeper()->getChildren(self::TEST_CLUSTER_NODES)
        );
    }

    public function testUnregisterNode()
    {
        $this->subject->create(self::TEST_CLUSTER_NAME);
        $this->subject->connect(self::TEST_CLUSTER_NAME);

        $node = new TestNode('node');
        $this->subject->registerNode($node);

        $this->assertCount(1, $this->subject->getZookeeper()->getChildren(self::TEST_CLUSTER_NODES));
        $this->assertContains(
            $node->getNodeIdentifier().'-0000000000',
            $this->subject->getZookeeper()->getChildren(self::TEST_CLUSTER_NODES)
        );

        $this->subject->unregisterNode($node);

        $this->assertCount(0, $this->subject->getZookeeper()->getChildren(self::TEST_CLUSTER_NODES));

        try {
            $this->subject->unregisterNode($node);
            $this->fail('Expected ClusterException');
        } catch (ClusterException $e) {
            $this->assertEquals($this->subject->getClusterIdentifier(), $e->getClusterIdentifier());
        }
    }

    /*
    public function testRegisterListener()
    {
        $node = new TestNode('node');
        $this->subject->registerNode($node);

        $this->subject->registerListener($node, 'Test\\Class1');
        $this->subject->registerListener($node, 'Test\\Class2');
        $this->subject->registerListener($node, 'Test\\AnotherClass');

        $this->assertCount(3, $this->subject->getZookeeper()->getChildren(self::TEST_CLUSTER_LISTENERS));
        $this->assertContains('Test.Class1', $this->subject->getZookeeper()->getChildren(self::TEST_CLUSTER_LISTENERS));
        $this->assertContains('Test.Class2', $this->subject->getZookeeper()->getChildren(self::TEST_CLUSTER_LISTENERS));
        $this->assertContains(
            'Test.AnotherClass',
            $this->subject->getZookeeper()->getChildren(self::TEST_CLUSTER_LISTENERS)
        );

        foreach ($this->subject->getZookeeper()->getChildren(self::TEST_CLUSTER_LISTENERS) as $child) {
            $this->assertCount(1, $this->subject->getZookeeper()->getChildren(self::TEST_CLUSTER_LISTENERS.'/'.$child));
            $this->assertContains(
                (string)$node,
                $this->subject->getZookeeper()->getChildren(self::TEST_CLUSTER_LISTENERS.'/'.$child)
            );
        }

    }

    public function testRegisterHandler()
    {
        $node1 = new TestNode('node');
        $this->subject->registerNode($node1);

        $node2 = new TestNode('node');
        $this->subject->registerNode($node2);

        $node3 = new TestNode('other');
        $this->subject->registerNode($node3);

        $this->subject->registerHandler($node1, 'Test\\Class1');
        $this->subject->registerHandler($node1, 'Test\\AnotherClass');

        $this->subject->registerHandler($node2, 'Test\\Class1');
        $this->subject->registerHandler($node2, 'Test\\Class2');

        $this->subject->registerHandler($node3, 'Test\\AnotherClass');

        $this->assertCount(3, $this->subject->getZookeeper()->getChildren(self::TEST_CLUSTER_HANDLERS));
        $this->assertContains('Test.Class1', $this->subject->getZookeeper()->getChildren(self::TEST_CLUSTER_HANDLERS));
        $this->assertContains('Test.Class2', $this->subject->getZookeeper()->getChildren(self::TEST_CLUSTER_HANDLERS));
        $this->assertContains(
            'Test.AnotherClass',
            $this->subject->getZookeeper()->getChildren(self::TEST_CLUSTER_HANDLERS)
        );

        foreach ($this->subject->getZookeeper()->getChildren(self::TEST_CLUSTER_HANDLERS) as $child) {
            $handlerNodes = $this->subject->getZookeeper()->getChildren(self::TEST_CLUSTER_HANDLERS.'/'.$child);

            switch ($child) {
                case 'Test.Class1':
                    $this->assertCount(2, $handlerNodes);
                    $this->assertContains('node-0000000000', $handlerNodes);
                    $this->assertContains('node-0000000001', $handlerNodes);
                    break;
                case 'Test.Class2':
                    $this->assertCount(1, $handlerNodes);
                    $this->assertContains('node-0000000001', $handlerNodes);
                    break;
                case 'Test.AnotherClass':
                    $this->assertCount(2, $handlerNodes);
                    $this->assertContains('node-0000000000', $handlerNodes);
                    $this->assertContains('other-0000000002', $handlerNodes);
                    break;
            }
        }

    }*/
}

class TestNode extends AbstractClusterNode
{
    protected function onClusterJoined(ClusterInterface $cluster, $sequence)
    {
        // TODO: Implement onClusterJoined() method.
    }

    protected function onClusterLeft(ClusterInterface $cluster, $sequence)
    {
        // TODO: Implement onClusterLeft() method.
    }

}