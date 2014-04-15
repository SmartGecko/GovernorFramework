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

/**
 * ClusterSelector implementation that always selects the same cluster. This implementation
 * can serve as delegate for other cluster selectors for event listeners that do not belong to a specific cluster.
 */
class DefaultClusterSelector implements ClusterSelectorInterface
{

    const DEFAULT_CLUSTER_IDENTIFIER = "default";

    private $defaultCluster;

    /**
     * Initializes the DefaultClusterSelector to assign the given <code>defaultCluster</code> to each listener.
     *
     * @param defaultCluster The Cluster to assign to each listener
     */
    public function __construct(ClusterInterface $defaultCluster = null)
    {
        if (null !== $defaultCluster) {
            $this->defaultCluster = $defaultCluster;
        } else {
            $this->defaultCluster = new SimpleCluster(self::DEFAULT_CLUSTER_IDENTIFIER);
        }
    }

    /**
     * {@inheritDoc}
     * <p/>
     * This implementation always returns the same instance of {@link SimpleCluster}.
     */
    public function selectCluster(EventListenerInterface $eventListener)
    {
        return $this->defaultCluster;
    }

}
