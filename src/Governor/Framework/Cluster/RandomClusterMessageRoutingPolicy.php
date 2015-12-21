<?php
/**
 * This file is part of the SmartGecko(c) business platform.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Governor\Framework\Cluster;


class RandomClusterMessageRoutingPolicy implements ClusterMessageRoutingPolicyInterface
{
    /**
     * @var ClusterMessageRegistryInterface
     */
    private $registry;

    /**
     * RandomClusterMessageRoutingPolicy constructor.
     *
     * @param ClusterMessageRegistryInterface $cluster
     */
    public function __construct(ClusterMessageRegistryInterface $cluster)
    {
        $this->registry = $cluster;
    }


    /**
     * @inheritDoc
     */
    public function getDestinationNode($messageType)
    {
        $nodes = $this->registry->findSuitableNodes($messageType);

        return empty($nodes) ? null : $nodes[rand(0, count($nodes) - 1)];
    }

}