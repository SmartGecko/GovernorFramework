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

abstract class AbstractClusterNode implements ClusterNodeInterface
{

    /**
     * @var string
     */
    private $nodeIdentifier;

    /**
     * @var ClusterInterface
     */
    private $cluster;

    /**
     * @var int
     */
    private $sequence;

    /**
     * AbstractClusterClusterNode constructor.
     * @param string $nodeIdentifier
     */
    public function __construct($nodeIdentifier)
    {
        $this->nodeIdentifier = $nodeIdentifier;
    }

    /**
     * @inheritDoc
     */
    public function getNodeIdentifier()
    {
        return $this->nodeIdentifier;
    }

    /**
     * @inheritDoc
     */
    public function getSequence()
    {
        return $this->sequence;
    }

    /**
     * @return ClusterInterface
     */
    protected function getCluster()
    {
        return $this->cluster;
    }

    /**
     * @inheritDoc
     */
    public function joinedCluster(ClusterInterface $cluster, $sequence)
    {
        $this->cluster = $cluster;
        $this->sequence = $sequence;

        $this->onClusterJoined($this->cluster, $this->sequence);
    }

    protected abstract function onClusterJoined(ClusterInterface $cluster, $sequence);

    protected abstract function onClusterLeft(ClusterInterface $cluster, $sequence);

    /**
     * @inheritDoc
     */
    public function leftCluster(ClusterInterface $cluster)
    {
        $this->onClusterLeft($this->cluster, $this->sequence);

        $this->cluster = null;
        $this->sequence = null;
    }

    /**
     * @inheritDoc
     */
    public function isJoined(ClusterInterface $cluster)
    {
        return null !== $this->cluster && $this->cluster === $cluster;
    }

    /**
     * @inheritDoc
     */
    function __toString()
    {
        return sprintf("%s-%s", $this->nodeIdentifier, $this->sequence);
    }

    /**
     * @inheritDoc
     */
    public function getEndpoint()
    {
        return sprintf(
            "governor.%s.%s-%s",
            $this->cluster->getClusterIdentifier(),
            $this->nodeIdentifier,
            $this->sequence
        );
    }

}