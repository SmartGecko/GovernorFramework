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

/**
 * Zookeeper implementation of the @see ClusterInterface
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
class ZookeeperCluster implements ClusterInterface, LoggerAwareInterface
{
    const ROOT_NODE = '/governor';

    const NODES_NODE = 'nodes';
    const LISTENERS_NODE = 'listeners';
    const LOCKS_NODE = 'locks';

    /**
     * @var \Zookeeper
     */
    private $zookeeper;

    /**
     * @var string
     */
    private $clusterIdentifier;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * ZookeeperCluster constructor.
     *
     * @param string $connectionString
     * @throws ClusterException
     */
    public function __construct($connectionString)
    {
        $this->logger = new NullLogger();

        try {
            $this->zookeeper = new \Zookeeper($connectionString);
        } catch (\Exception $e) {
            throw new ClusterException($this->clusterIdentifier, 'Could not connect to zookeeper', $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function create($clusterIdentifier)
    {
        $this->clusterIdentifier = $clusterIdentifier;
        $pathName = $this->getRootNodeName();

        if ($this->zookeeper->exists($pathName)) {
            $this->logger->info(
                'Cluster structures already in place for {cluster}',
                ['cluster' => $this->clusterIdentifier]
            );

            return;
        }

        $this->logger->info('Creating cluster node for {cluster}', ['cluster' => $this->clusterIdentifier]);

        $this->set($pathName, $this->clusterIdentifier);
        $this->set(sprintf("%s/%s", $pathName, self::NODES_NODE), self::NODES_NODE);
        $this->set(sprintf("%s/%s", $pathName, self::LISTENERS_NODE), self::LISTENERS_NODE);
        $this->set(sprintf("%s/%s", $pathName, self::LOCKS_NODE), self::LOCKS_NODE);
    }

    /**
     * @inheritDoc
     */
    public function connect($clusterIdentifier)
    {
        $this->clusterIdentifier = $clusterIdentifier;

        if (!$this->zookeeper->exists($this->getRootNodeName())) {
            $this->logger->error(
                'Cluster {cluster} was not yet initialized.',
                ['cluster' => $this->clusterIdentifier]
            );

            throw new ClusterException($this->clusterIdentifier, 'Cluster not initialized.');
        }

        $this->logger->debug('Connected to cluster {cluster}', ['cluster' => $clusterIdentifier]);
    }

    private function assertConnected()
    {
        if (!$this->clusterIdentifier) {
            throw new ClusterException($this->clusterIdentifier, 'Not connected to cluster.');
        }
    }

    /**
     * @inheritDoc
     */
    public function drop($clusterIdentifier)
    {
        $this->clusterIdentifier = $clusterIdentifier;

        if (!$this->zookeeper->exists($this->getRootNodeName())) {
            $this->logger->error(
                'Cluster {cluster} was not yet initialized.',
                ['cluster' => $this->clusterIdentifier]
            );

            throw new ClusterException($this->clusterIdentifier, 'Cluster not initialized.');
        }

        $this->clearNodes($this->getRootNodeName());
        $this->clusterIdentifier = null;
    }


    /**
     * @return \Zookeeper
     */
    public function getZookeeper()
    {
        return $this->zookeeper;
    }

    /**
     * @param string $node
     */
    private function clearNodes($node)
    {
        foreach ($this->zookeeper->getChildren($node) as $child) {
            $this->clearNodes(sprintf("%s/%s", $node, $child));
        }

        $this->zookeeper->delete($node);
    }

    /**
     * @return string
     */
    private function getRootNodeName()
    {
        return sprintf("%s/%s", self::ROOT_NODE, $this->clusterIdentifier);
    }

    /**
     * Set a node to a value. If the node doesn't exist yet, it is created.
     * Existing values of the node are overwritten
     *
     * @param string $path The path to the node
     * @param mixed $value The new value for the node
     *
     * @return mixed previous value if set, or null
     */
    public function set($path, $value)
    {
        if (!$this->zookeeper->exists($path)) {
            $this->makePath($path);
            $this->makeNode($path, $value);
        } else {
            $this->zookeeper->set($path, $value);
        }
    }

    /**
     * Equivalent of "mkdir -p" on ZooKeeper
     *
     * @param string $path The path to the node
     * @param string $value The value to assign to each new node along the path
     *
     * @return bool
     */
    public function makePath($path, $value = '')
    {
        $parts = explode('/', $path);
        $parts = array_filter($parts);
        $subPath = '';

        while (count($parts) > 1) {
            $subPath .= '/'.array_shift($parts);

            if (!$this->zookeeper->exists($subPath)) {
                $this->makeNode($subPath, $value);
            }
        }
    }

    /**
     * Create a node on ZooKeeper at the given path
     *
     * @param string $path The path to the node
     * @param string $value The value to assign to the new node
     * @param array $params Optional parameters for the Zookeeper node.
     *                       By default, a public node is created
     * @param int $flags
     *
     * @return string the path to the newly created node or null on failure
     */
    public function makeNode($path, $value, array $params = [], $flags = null)
    {
        if (empty($params)) {
            $params = [
                [
                    'perms' => \Zookeeper::PERM_ALL,
                    'scheme' => 'world',
                    'id' => 'anyone',
                ]
            ];
        }

        return $this->zookeeper->create($path, $value, $params, $flags);
    }

    /**
     * @inheritDoc
     */
    public function getClusterIdentifier()
    {
        return $this->clusterIdentifier;
    }


    /**
     * @inheritdoc
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param string $path
     * @return string
     * @throws ClusterException
     */
    private function parseSequence($path)
    {
        if (preg_match('/^.*\-([0-9]+)$/', $path, $matches)) {
            return $matches[1];
        }

        throw new ClusterException($this->clusterIdentifier, sprintf('Unexpected cluster node name %s', $path));
    }

    /**
     * @inheritDoc
     */
    public function registerNode(ClusterNodeInterface $node)
    {
        $this->assertConnected();

        $pathName = sprintf("%s/%s/%s-", $this->getRootNodeName(), self::NODES_NODE, $node->getNodeIdentifier());
        $created = $this->makeNode(
            $pathName,
            $node->getNodeIdentifier(),
            [],
            \Zookeeper::EPHEMERAL | \Zookeeper::SEQUENCE
        );

        $sequence = $this->parseSequence($created);

        $this->logger->info(
            'Registered node [{node}], sequence [{sequence}] with cluster [{cluster}]',
            [
                'node' => get_class($node),
                'sequence' => $sequence,
                'cluster' => $this->clusterIdentifier
            ]
        );

        $node->joinedCluster($this, $sequence);
    }

    /**
     * @inheritDoc
     */
    public function unregisterNode(ClusterNodeInterface $node)
    {
        $this->assertConnected();

        $pathName = sprintf(
            "%s/%s/%s-%s",
            $this->getRootNodeName(),
            self::NODES_NODE,
            $node->getNodeIdentifier(),
            $node->getSequence()
        );

        if (!$this->zookeeper->exists($pathName)) {
            $this->logger->error(
                'Node [{node}] not registered in cluster [{cluster}] ',
                [
                    'node' => $node->getNodeIdentifier(),
                    'cluster' => $this->clusterIdentifier
                ]
            );

            throw new ClusterException(
                $this->clusterIdentifier,
                sprintf(
                    'Node [%s] not registered in cluster [%s] ',
                    $node->getNodeIdentifier(),
                    $this->clusterIdentifier
                )
            );
        }

        $this->zookeeper->delete($pathName);
        $node->leftCluster($this);
    }

    /**
     * @inheritDoc
     */
    public function createMessageRegistry($namespace)
    {
        $this->assertConnected();

        $path = sprintf("%s/%s", $this->getRootNodeName(), $namespace);
        $this->set($path, $namespace);

        $registry = new ZookeeperClusterMessageRegistry($this, $path);
        $registry->setLogger($this->logger);

        return $registry;
    }

    /**
     * @inheritDoc
     */
    public function createLockRegistry()
    {
        $registry = new ZookeeperClusterLockRegistry($this);
        $registry->setLogger($this->logger);

        return $registry;
    }

}