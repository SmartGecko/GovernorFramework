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

namespace Governor\Framework\Test\Saga;

use Governor\Framework\Saga\AssociationValue;
use Governor\Framework\Test\GovernorAssertionError;
use Governor\Framework\Saga\Repository\Memory\InMemorySagaRepository;

class RepositoryContentValidator
{
    /**
     * @var InMemorySagaRepository
     */
    private $sagaRepository;
    /**
     * @var string
     */
    private $sagaType;

    /**
     * Initialize the validator to validate contents of the given <code>sagaRepository</code>, which contains Sagas of
     * the given <code>sagaType</code>.
     *
     * @param InMemorySagaRepository $sagaRepository The repository to monitor
     * @param string $sagaType The type of saga to validate
     */
    public function __construct(InMemorySagaRepository $sagaRepository, $sagaType)
    {
        $this->sagaRepository = $sagaRepository;
        $this->sagaType = $sagaType;
    }

    /**
     * Asserts that an association is present for the given <code>associationKey</code> and
     * <code>associationValue</code>.
     *
     * @param string $associationKey The key of the association
     * @param string $associationValue The value of the association
     * @throws GovernorAssertionError
     */
    public function assertAssociationPresent($associationKey, $associationValue)
    {
        $associatedSagas = $this->sagaRepository->find(
            $this->sagaType,
            new AssociationValue($associationKey, $associationValue)
        );

        if (empty($associatedSagas)) {
            throw new GovernorAssertionError(
                sprintf(
                    "Expected a saga to be associated with key:<%s> value:<%s>, but found none",
                    $associationKey,
                    $associationValue
                )
            );
        }
    }

    /**
     * Asserts that <em>no</em> association is present for the given <code>associationKey</code> and
     * <code>associationValue</code>.
     *
     * @param string $associationKey The key of the association
     * @param string $associationValue The value of the association
     * @throws GovernorAssertionError
     */
    public function assertNoAssociationPresent($associationKey, $associationValue)
    {
        $associatedSagas = $this->sagaRepository->find(
            $this->sagaType,
            new AssociationValue($associationKey, $associationValue)
        );

        if (!empty($associatedSagas)) {
            throw new GovernorAssertionError(
                sprintf(
                    "Expected a saga to be associated with key:<%s> value:<%s>, but found <%s>",
                    $associationKey,
                    $associationValue
                )
            );
        }
    }

    /**
     * Asserts that the repository contains the given <code>expected</code> amount of active sagas.
     *
     * @param int $expected The number of expected sagas.
     * @throws GovernorAssertionError
     */
    public function assertActiveSagas($expected)
    {
        if ($expected !== $this->sagaRepository->size()) {
            throw new GovernorAssertionError(
                sprintf("Wrong number of active sagas. Expected <%s>, got <%s>.",
                    $expected,
                    $this->sagaRepository->size()
                )
            );
        }
    }
}
