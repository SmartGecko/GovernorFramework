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

namespace Governor\Framework\CommandHandling\Distributed;

use Governor\Framework\CommandHandling\CommandMessageInterface;


/**
 * RoutingStrategy implementation that uses the value in the MetaData of a CommandMessage assigned to a given key. The
 * value's <code>toString()</code> is used to convert the MetaData value to a String.
 *
 * @author Allard Buijze
 * @since 2.0
 */
class MetaDataRoutingStrategy extends AbstractRoutingStrategy
{

    /**
     * @var string
     */
    private $metaDataKey;


    /**
     * Initializes the MetaDataRoutingStrategy where the given <code>metaDataKey</code> is used to get the Meta Data
     * value. The given <code>unresolvedRoutingKeyPolicy</code> presecribes what to do when the Meta Data properties
     * cannot be found.
     *
     * @param string $metaDataKey The key on which the value is retrieved from the MetaData.
     * @param int $unresolvedRoutingKeyPolicy The policy prescribing behavior when the routing key cannot be resolved
     */
    public function __construct($metaDataKey, $unresolvedRoutingKeyPolicy = UnresolvedRoutingKeyPolicy::ERROR)
    {
        parent::__construct($unresolvedRoutingKeyPolicy);
        $this->metaDataKey = $metaDataKey;
    }

    /**
     * @param CommandMessageInterface $command
     * @return null|string
     */
    protected function doResolveRoutingKey(CommandMessageInterface $command)
    {
        $value = $command->getMetaData()->get($this->metaDataKey);
        return isset($value) ? (string)$value : null;
    }
}