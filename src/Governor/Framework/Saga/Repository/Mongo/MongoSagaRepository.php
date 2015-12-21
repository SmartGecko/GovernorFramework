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

namespace Governor\Framework\Saga\Repository\Mongo;

use Governor\Framework\Saga\AssociationValue;
use Governor\Framework\Saga\SagaInterface;
use Governor\Framework\Serializer\SerializerInterface;
use Governor\Framework\Saga\ResourceInjectorInterface;
use Governor\Framework\Saga\Repository\AbstractSagaRepository;
use Governor\Framework\Serializer\SimpleSerializedObject;
use Governor\Framework\Serializer\SimpleSerializedType;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Governor\Framework\Common\Logging\NullLogger;

/**
 * Implementations of the SagaRepository that stores Sagas and their associations in a Mongo Database. Each Saga and
 * its associations is stored as a single document.
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
class MongoSagaRepository extends AbstractSagaRepository implements LoggerAwareInterface
{

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var MongoTemplateInterface
     */
    private $mongoTemplate;
    /**
     * @var SerializerInterface
     */
    private $serializer;
    /**
     * @var ResourceInjectorInterface
     */
    private $injector;

    /**
     * Initializes the Repository, using given <code>mongoTemplate</code> to access the collections containing the
     * stored Saga instances.
     *
     * @param MongoTemplateInterface $mongoTemplate the template providing access to the collections
     * @param ResourceInjectorInterface $injector
     * @param SerializerInterface $serializer
     */
    public function __construct(
        MongoTemplateInterface $mongoTemplate,
        ResourceInjectorInterface $injector,
        SerializerInterface $serializer
    ) {
        $this->mongoTemplate = $mongoTemplate;
        $this->serializer = $serializer;
        $this->injector = $injector;

        $this->logger = new NullLogger();
    }

    /**
     * Finds the identifiers of the sagas of given <code>type</code> associated with the given
     * <code>associationValue</code>.
     *
     * @param string $type The type of saga to find identifiers for
     * @param AssociationValue $associationValue The value the saga must be associated with
     * @return array The identifiers of sagas associated with the given <code>associationValue</code>
     */
    protected function findAssociatedSagaIdentifiers(
        $type,
        AssociationValue $associationValue
    ) {
        $value = $this->associationValueQuery($type, $associationValue);

        $dbCursor = $this->mongoTemplate->sagaCollection()->find($value, ["sagaIdentifier" => 1]);
        $found = [];

        while ($dbCursor->hasNext()) {
            $found[] = $dbCursor->getNext()['sagaIdentifier'];
        }

        return $found;
    }

    /**
     * Returns the type identifier to use for the given <code>sagaClass</code>. This information is typically provided
     * by the Serializer, if the repository stores serialized instances.
     *
     * @param mixed $sagaClass The type of saga to get the type identifier for.
     * @return string The type identifier to use for the given sagaClass
     */
    protected function typeOf($sagaClass)
    {
        if (is_object($sagaClass)) {
            return $this->serializer->typeForClass($sagaClass)->getName();
        }

        return $sagaClass;
    }

    /**
     * Remove the given saga as well as all known association values pointing to it from the repository. If no such
     * saga exists, nothing happens.
     *
     * @param SagaInterface $saga The saga instance to remove from the repository
     */
    protected function deleteSaga(SagaInterface $saga)
    {
        $this->mongoTemplate->sagaCollection()->remove(SagaEntry::queryByIdentifier($saga->getSagaIdentifier()));
    }

    /**
     * Update a stored Saga, by replacing it with the given <code>saga</code> instance.
     *
     * @param SagaInterface $saga The saga that has been modified and needs to be updated in the storage
     */
    protected function updateSaga(SagaInterface $saga)
    {
        $sagaEntry = new SagaEntry($saga, $this->serializer);

        $this->mongoTemplate->sagaCollection()->findAndModify(
            SagaEntry::queryByIdentifier($saga->getSagaIdentifier()),
            $sagaEntry->asDBObject()
        );
    }

    /**
     * Stores a newly created Saga instance.
     *
     * @param SagaInterface $saga The newly created Saga instance to store.
     */
    protected function storeSaga(SagaInterface $saga)
    {
        $sagaEntry = new SagaEntry($saga, $this->serializer);
        $sagaObject = $sagaEntry->asDBObject();

        $this->mongoTemplate->sagaCollection()->insert($sagaObject);
    }

    /**
     * Store the given <code>associationValue</code>, which has been associated with given <code>sagaIdentifier</code>.
     *
     * @param AssociationValue $associationValue The association value to store
     * @param string $sagaType Type type of saga the association value belongs to
     * @param string $sagaIdentifier The saga related to the association value
     */
    protected function storeAssociationValue(
        AssociationValue $associationValue,
        $sagaType,
        $sagaIdentifier
    ) {
        $this->mongoTemplate->sagaCollection()->update(
            ['sagaIdentifier' => $sagaIdentifier, 'sagaType' => $sagaType],
            [
                '$push' => [
                    'associations' => [
                        'key' => $associationValue->getPropertyKey(),
                        'value' => $associationValue->getPropertyValue()
                    ]
                ]
            ]
        );
    }

    /**
     * Removes the association value that has been associated with Saga, identified with the given
     * <code>sagaIdentifier</code>.
     *
     * @param AssociationValue $associationValue The value to remove as association value for the given saga
     * @param string $sagaType The type of the Saga to remove the association from
     * @param string $sagaIdentifier The identifier of the Saga to remove the association from
     */
    protected function removeAssociationValue(
        AssociationValue $associationValue,
        $sagaType,
        $sagaIdentifier
    ) {
        $this->mongoTemplate->sagaCollection()->update(
            ['sagaIdentifier' => $sagaIdentifier, 'sagaType' => $sagaType],
            [
                '$pull' => [
                    'associations' => [
                        'key' => $associationValue->getPropertyKey(),
                        'value' => $associationValue->getPropertyValue()
                    ]
                ]
            ]
        );
    }

    /**
     * Loads a known Saga instance by its unique identifier. Returned Sagas must be {@link #commit(Saga) committed}
     * after processing.
     * Due to the concurrent nature of Sagas, it is not unlikely for a Saga to have ceased to exist after it has been
     * found based on associations. Therefore, a repository should return <code>null</code> in case a Saga doesn't
     * exists, as opposed to throwing an exception.
     *
     * @param string $sagaIdentifier The unique identifier of the Saga to load
     * @return SagaInterface The Saga instance, or <code>null</code> if no such saga exists
     */
    public function load($sagaIdentifier)
    {
        $dbSaga = $this->mongoTemplate->sagaCollection()->findOne(SagaEntry::queryByIdentifier($sagaIdentifier));

        if (null === $dbSaga) {
            return null;
        }

        $serializedSaga = new SimpleSerializedObject(
            $dbSaga[SagaEntry::SERIALIZED_SAGA],
            new SimpleSerializedType($dbSaga[SagaEntry::SAGA_TYPE])
        );

        $saga = $this->serializer->deserialize($serializedSaga);

        if (null !== $this->injector) {
            $this->injector->injectResources($saga);
        }

        return $saga;
    }


    private function associationValueQuery($type, AssociationValue $associationValue)
    {
        return [
            'sagaType' => $this->typeOf($type),
            'associations' => [
                'key' => $associationValue->getPropertyKey(),
                'value' => $associationValue->getPropertyValue()
            ]
        ];
    }

    /**
     * Sets a logger instance on the object
     *
     * @param LoggerInterface $logger
     * @return null
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }


}