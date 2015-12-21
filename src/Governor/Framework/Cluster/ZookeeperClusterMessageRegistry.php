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

namespace Governor\Framework\Cluster;

use Governor\Framework\Common\Logging\NullLogger;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

class ZookeeperClusterMessageRegistry implements ClusterMessageRegistryInterface, LoggerAwareInterface
{
    /**
     * @var ZookeeperCluster
     */
    private $cluster;

    /**
     * @var string
     */
    private $rootNode;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * ZookeeperClusterMessageRegistry constructor.
     *
     * @param ZookeeperCluster $cluster
     * @param string $rootNode
     */
    public function __construct(ZookeeperCluster $cluster, $rootNode)
    {
        $this->cluster = $cluster;
        $this->rootNode = $rootNode;
        $this->logger = new NullLogger();
    }


    /**
     * @inheritDoc
     */
    public function getCluster()
    {
        return $this->cluster;
    }

    /**
     * @inheritDoc
     */
    public function getRoutingPolicy()
    {
        return new RandomClusterMessageRoutingPolicy($this);
    }


    /**
     * @inheritDoc
     */
    public function registerMessage(ClusterNodeInterface $node, $messageType)
    {
        if (!$node->isJoined($this->cluster)) {
            throw new ClusterException(
                $this->cluster->getClusterIdentifier(),
                'Node [%s] not registered in cluster[%s]',
                $node,
                $this->cluster->getClusterIdentifier()
            );
        }

        $pathName = $this->getMessageNode($messageType);

        if (!$this->cluster->getZookeeper()->exists($pathName)) {
            $this->cluster->makeNode($pathName, $messageType);
        }

        $this->cluster->makeNode(
            $pathName.'/'.(string)$node,
            $node->getEndpoint(),
            [],
            \Zookeeper::EPHEMERAL
        );

        $this->logger->info(
            'Message [{msg}] registered for node [{node}]',
            [
                'msg' => $messageType,
                'node' => (string)$node
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function unregisterMessage(ClusterNodeInterface $node, $messageType)
    {
        // TODO: Implement unregister() method.
    }

    private function convertMessageType($messageType)
    {
        return strtr($messageType, '\\', '.');
    }

    /**
     * @param string $messageType
     * @return string
     */
    private function getMessageNode($messageType)
    {
        $pathName = sprintf(
            "%s/%s",
            $this->rootNode,
            $this->convertMessageType($messageType)
        );

        return $pathName;
    }

    /**
     * @inheritDoc
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function findSuitableNodes($messageType)
    {
        $node = $this->getMessageNode($messageType);

        if (!$this->cluster->getZookeeper()->exists($node)) {
            return [];
        }

        return array_map(
            function ($child) use ($node) {
                return $this->cluster->getZookeeper()->get($node.'/'.$child);
            },
            $this->cluster->getZookeeper()->getChildren($node)
        );
    }


}