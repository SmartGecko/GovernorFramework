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

namespace Governor\Tests\Saga\Repository;

use Governor\Framework\Saga\SagaInterface;
use Governor\Framework\Saga\SagaRepositoryInterface;
use Governor\Framework\Saga\AssociationValue;

/**
 * Description of InMemorySagaRepository
 *
 * @author david
 */
class InMemorySagaRepository implements SagaRepositoryInterface
{

    /**
     * @var array
     */
    private $managedSagas = array();

    public function add(SagaInterface $saga)
    {
        $this->commit($saga);
    }

    public function commit(SagaInterface $saga)
    {
        if (!$saga->isActive()) {
            unset($this->managedSagas[$saga->getSagaIdentifier()]);
        } else {
            $this->managedSagas[$saga->getSagaIdentifier()] = $saga;
        }

        $saga->getAssociationValues()->commit();
    }

    public function find($type, AssociationValue $associationValue)
    {        
        $result = array();

        foreach ($this->managedSagas as $id => $saga) {
            if ($saga->getAssociationValues()->contains($associationValue) && $type
                    === get_class($saga)) {
                $result[] = $saga->getSagaIdentifier();
            }
        }

        return $result;
    }

    public function load($sagaIdentifier)
    {
        return $this->managedSagas[$sagaIdentifier];
    }

    public function size()
    {
        return count($this->managedSagas);
    }

}
