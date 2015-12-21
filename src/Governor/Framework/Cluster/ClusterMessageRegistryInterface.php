<?php
/**
 * This file is part of the SmartGecko(c) business platform.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Governor\Framework\Cluster;


interface ClusterMessageRegistryInterface
{

    /**
     * @return ClusterInterface
     */
    public function getCluster();

    /**
     * @return ClusterMessageRoutingPolicyInterface
     */
    public function getRoutingPolicy();

    /**
     * @param ClusterNodeInterface $node
     * @param string $messageType
     */
    public function registerMessage(ClusterNodeInterface $node, $messageType);

    /**
     * @param ClusterNodeInterface $node
     * @param string $messageType
     */
    public function unregisterMessage(ClusterNodeInterface $node, $messageType);

    /**
     * @param string $messageType
     * @return []
     */
    public function findSuitableNodes($messageType);

}