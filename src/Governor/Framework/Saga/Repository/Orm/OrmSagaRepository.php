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

namespace Governor\Framework\Saga\Repository\Orm;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NoResultException;
use Governor\Framework\Saga\Repository\AbstractSagaRepository;
use Governor\Framework\Saga\ResourceInjectorInterface;
use Governor\Framework\Saga\AssociationValue;
use Governor\Framework\Saga\SagaInterface;
use Governor\Framework\Serializer\SerializerInterface;
use Governor\Framework\Serializer\SimpleSerializedType;
use Governor\Framework\Common\Logging\NullLogger;

/**
 * Description of OrmSagaRepository
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
class OrmSagaRepository extends AbstractSagaRepository implements LoggerAwareInterface
{

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var ResourceInjectorInterface
     */
    private $injector;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var boolean
     */
    private $useExplicitFlush;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Initializes a Saga Repository.
     *
     * @param \Doctrine\ORM\EntityManager $entityManager
     * @param \Governor\Framework\Saga\ResourceInjectorInterface $injector
     * @param \Governor\Framework\Serializer\SerializerInterface $serializer
     * @param boolean $useExplicitFlush
     */
    public function __construct(
        EntityManager $entityManager,
        ResourceInjectorInterface $injector,
        SerializerInterface $serializer,
        $useExplicitFlush = true
    ) {
        $this->entityManager = $entityManager;
        $this->injector = $injector;
        $this->serializer = $serializer;
        $this->useExplicitFlush = $useExplicitFlush;

        $this->logger = new NullLogger();
    }

    public function load($sagaId)
    {
        try {
            $result = $this->entityManager->createQuery(
                "SELECT se FROM Governor\Framework\Saga\Repository\Orm\SagaEntry se WHERE se.sagaId = :sagaId"
            )
                ->setParameter(":sagaId", $sagaId)->getSingleResult();

            $serializedSaga = new SerializedSaga(
                $result->getSerializedSaga(),
                new SimpleSerializedType(
                    $result->getSagaType(),
                    $result->getRevision()
                )
            );

            $loadedSaga = $this->serializer->deserialize($serializedSaga);

            if (null !== $this->injector) {
                $this->injector->injectResources($loadedSaga);
            }

            $this->logger->debug(
                "Loaded saga id [{id}] of type [{type}]",
                array('id' => $sagaId, 'type' => get_class($loadedSaga))
            );

            return $loadedSaga;
        } catch (NoResultException $ex) {
            return null;
        }
    }

    protected function removeAssociationValue(
        AssociationValue $associationValue,
        $sagaType,
        $sagaIdentifier
    ) {
        $updateCount = $this->entityManager->createQuery(
            "DELETE FROM ".
            " Governor\Framework\Saga\Repository\Orm\AssociationValueEntry ae ".
            "WHERE ae.associationKey = :associationKey ".
            "AND ae.associationValue = :associationValue ".
            "AND ae.sagaType = :sagaType ".
            "AND ae.sagaId = :sagaId"
        )
            ->setParameters(
                [
                    ':associationKey' => $associationValue->getPropertyKey(),
                    ':associationValue' => $associationValue->getPropertyValue(),
                    ':sagaType' => $sagaType,
                    ':sagaId' => $sagaIdentifier
                ]
            )->execute();

        if (0 === $updateCount) {
            $this->logger->warning(
                "Wanted to remove association value, but it was already gone: sagaId= {sagaId}, key={key}, value={value}",
                array(
                    'sagaId' => $sagaIdentifier,
                    'key' => $associationValue->getPropertyKey(),
                    'value' => $associationValue->getPropertyValue()
                )
            );
        }
    }

    protected function typeOf($sagaClass)
    {
        if (is_object($sagaClass)) {
            return $this->serializer->typeForClass($sagaClass)->getName();
        }

        return $sagaClass;
    }

    protected function storeAssociationValue(
        AssociationValue $associationValue,
        $sagaType,
        $sagaIdentifier
    ) {
        $this->entityManager->persist(
            new AssociationValueEntry(
                $sagaType,
                $sagaIdentifier, $associationValue
            )
        );
        if ($this->useExplicitFlush) {
            $this->entityManager->flush();
        }
    }

    protected function findAssociatedSagaIdentifiers(
        $type,
        AssociationValue $associationValue
    ) {
        $entries = $this->entityManager->createQuery(
            "SELECT ae.sagaId FROM ".
            "Governor\Framework\Saga\Repository\Orm\AssociationValueEntry ae ".
            "WHERE ae.associationKey = :associationKey ".
            "AND ae.associationValue = :associationValue ".
            "AND ae.sagaType = :sagaType"
        )
            ->setParameters(
                array(
                    ":associationKey" => $associationValue->getPropertyKey(),
                    ":associationValue" => $associationValue->getPropertyValue(),
                    ":sagaType" => $this->typeOf($type)
                )
            )->getResult();

        return array_map('current', $entries);
    }

    protected function deleteSaga(SagaInterface $saga)
    {
        try {
            $this->entityManager->createQuery(
                "DELETE FROM Governor\Framework\Saga\Repository\Orm\AssociationValueEntry ae WHERE ae.sagaId = :sagaId"
            )
                ->setParameter(":sagaId", $saga->getSagaIdentifier())->execute();

            $this->entityManager->createQuery(
                "DELETE FROM Governor\Framework\Saga\Repository\Orm\SagaEntry se WHERE se.sagaId = :id"
            )
                ->setParameter(":id", $saga->getSagaIdentifier())->execute();
        } catch (NoResultException $ex) {
            $this->logger->info(
                "Could not delete SagaEntry {id}, it appears to have already been deleted.",
                array('id' => $saga->getSagaIdentifier())
            );
        }
        $this->entityManager->flush();
    }

    protected function updateSaga(SagaInterface $saga)
    {
        $entry = new SagaEntry($saga, $this->serializer);

        $this->logger->debug(
            "Updating saga id {id} as {data}",
            array('id' => $saga->getSagaIdentifier(), 'data' => $entry->getSerializedSaga())
        );

        $updateCount = $this->entityManager->createQuery(
            "UPDATE Governor\Framework\Saga\Repository\Orm\SagaEntry s ".
            "SET s.serializedSaga = :serializedSaga, s.revision = :revision ".
            "WHERE s.sagaId = :sagaId AND s.sagaType = :sagaType"
        )
            ->setParameters(
                array(
                    ":serializedSaga" => $entry->getSerializedSaga(),
                    ":revision" => $entry->getRevision(),
                    ":sagaId" => $entry->getSagaId(),
                    ":sagaType" => $entry->getSagaType()
                )
            )->execute();

        if ($this->useExplicitFlush) {
            $this->entityManager->flush();
            $this->entityManager->clear();
        }

        if (0 === $updateCount) {
            $this->logger->warning(
                "Expected to be able to update a Saga instance, but no rows were found. Inserting instead."
            );

            $this->entityManager->persist($entry);
            if ($this->useExplicitFlush) {
                $this->entityManager->flush();
            }
        }
    }

    protected function storeSaga(SagaInterface $saga)
    {
        $entry = new SagaEntry($saga, $this->serializer);
        $this->entityManager->persist($entry);

        $this->logger->debug(
            "Storing saga id {id} as {data}",
            array('id' => $saga->getSagaIdentifier(), 'data' => $entry->getSerializedSaga())
        );

        if ($this->useExplicitFlush) {
            $this->entityManager->flush();
        }
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

}
