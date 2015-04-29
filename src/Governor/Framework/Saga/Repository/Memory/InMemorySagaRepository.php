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

namespace Governor\Framework\Saga\Repository\Memory;

use Governor\Framework\Saga\SagaInterface;
use Governor\Framework\Saga\SagaRepositoryInterface;
use Governor\Framework\Saga\AssociationValue;

/**
 * SagaRepositoryInterface implementation that stores data in an in memory array;
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
class InMemorySagaRepository implements SagaRepositoryInterface
{

    /**
     * @var SagaInterface[]
     */
    private $managedSagas = array();

    /**
     * {@inheritdoc}
     */
    public function add(SagaInterface $saga)
    {
        $this->commit($saga);
    }

    /**
     * {@inheritdoc}
     */
    public function commit(SagaInterface $saga)
    {
        if (!$saga->isActive()) {
            unset($this->managedSagas[$saga->getSagaIdentifier()]);
        } else {
            $this->managedSagas[$saga->getSagaIdentifier()] = $saga;
        }

        $saga->getAssociationValues()->commit();
    }

    /**
     * {@inheritdoc}
     */
    public function find($type, AssociationValue $associationValue)
    {
        $result = array();

        foreach ($this->managedSagas as $id => $saga) {
            if ($saga->getAssociationValues()->contains($associationValue) && $type
                === get_class($saga)
            ) {
                $result[] = $saga->getSagaIdentifier();
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function load($sagaIdentifier)
    {
        return $this->managedSagas[$sagaIdentifier];
    }

    /**
     * @return int
     */
    public function size()
    {
        return count($this->managedSagas);
    }

}
