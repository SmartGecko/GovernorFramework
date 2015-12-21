<?php
/**
 * This file is part of the SmartGecko(c) business platform.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Governor\Framework\Cluster;


interface ClusterMessageRoutingPolicyInterface
{
    /**
     * @param string $messageType
     * @return string
     */
    public function getDestinationNode($messageType);
}