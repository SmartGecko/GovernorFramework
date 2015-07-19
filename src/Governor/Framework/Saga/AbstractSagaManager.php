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

namespace Governor\Framework\Saga;

use Governor\Framework\Common\Logging\NullLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareInterface;
use Governor\Framework\Correlation\CorrelationDataHolder;
use Governor\Framework\Correlation\CorrelationDataProviderInterface;
use Governor\Framework\Correlation\SimpleCorrelationDataProvider;
use Governor\Framework\Correlation\MultiCorrelationDataProvider;
use Governor\Framework\Domain\EventMessageInterface;

/**
 * Base SagaManagerInterface implementation.
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
abstract class AbstractSagaManager implements SagaManagerInterface, LoggerAwareInterface
{

    /**
     * @var SagaRepositoryInterface
     */
    private $sagaRepository;

    /**
     * @var SagaFactoryInterface
     */
    private $sagaFactory;

    /**
     * @var array
     */
    private $sagaTypes = [];

    /**
     * @var boolean
     */
    private $suppressExceptions = true;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var CorrelationDataProviderInterface
     */
    private $correlationDataProvider;

    /**
     * @param SagaRepositoryInterface $sagaRepository
     * @param SagaFactoryInterface $sagaFactory
     * @param array $sagaTypes
     */
    public function __construct(
        SagaRepositoryInterface $sagaRepository,
        SagaFactoryInterface $sagaFactory,
        array $sagaTypes = []
    ) {
        $this->sagaRepository = $sagaRepository;
        $this->sagaFactory = $sagaFactory;
        $this->sagaTypes = $sagaTypes;
        $this->correlationDataProvider = new SimpleCorrelationDataProvider();
        $this->logger = new NullLogger();
    }

    /**
     * {@inheritdoc}
     */
    public function handle(EventMessageInterface $event)
    {
        foreach ($this->sagaTypes as $sagaType) {
            $associationValues = $this->extractAssociationValues(
                $sagaType,
                $event
            );

            if (null !== $associationValues && !empty($associationValues)) {
                $sagaOfTypeInvoked = $this->invokeExistingSagas(
                    $event,
                    $sagaType,
                    $associationValues
                );
                $initializationPolicy = $this->getSagaCreationPolicy(
                    $sagaType,
                    $event
                );
                if ($initializationPolicy->getCreationPolicy() === SagaCreationPolicy::ALWAYS
                    || (!$sagaOfTypeInvoked && $initializationPolicy->getCreationPolicy()
                        === SagaCreationPolicy::IF_NONE_FOUND)
                ) {
                    $this->startNewSaga(
                        $event,
                        $sagaType,
                        $initializationPolicy->getInitialAssociationValue()
                    );
                }
            }
        }
    }

    private function containsAny(
        AssociationValuesInterface $associationValues,
        array $toFind
    ) {
        foreach ($toFind as $valueToFind) {
            if ($associationValues->contains($valueToFind)) {
                return true;
            }
        }

        return false;
    }

    private function startNewSaga(
        EventMessageInterface $event,
        $sagaType,
        AssociationValue $associationValue
    ) {
        $newSaga = $this->sagaFactory->createSaga($sagaType);
        $newSaga->getAssociationValues()->add($associationValue);
        $this->preProcessSaga($newSaga);

        try {
            $this->doInvokeSaga($event, $newSaga);
        } finally {
            $this->sagaRepository->add($newSaga);
        }
    }

    private function invokeExistingSagas(
        EventMessageInterface $event,
        $sagaType,
        $associationValues
    ) {
        $sagas = [];

        foreach ($associationValues as $associationValue) {
            $sagas = $this->sagaRepository->find($sagaType, $associationValue);
        }

        $sagaOfTypeInvoked = false;

        foreach ($sagas as $sagaId) {
            $saga = $this->loadAndInvoke($event, $sagaId, $associationValues);

            if (null !== $saga) {
                $sagaOfTypeInvoked = true;
            }
        }

        return $sagaOfTypeInvoked;
    }

    private function loadAndInvoke(
        EventMessageInterface $event,
        $sagaId,
        array $associations
    ) {
        $saga = $this->sagaRepository->load($sagaId);

        if (null === $saga || !$saga->isActive() || !$this->containsAny(
                $saga->getAssociationValues(),
                $associations
            )
        ) {
            return null;
        }

        $this->preProcessSaga($saga);
        $exception = null;

        try {
            $this->logger->info(
                "Saga {saga} is handling event {event}",
                [
                    'saga' => $sagaId,
                    'event' => $event->getPayloadType()
                ]
            );
            $saga->handle($event);
        } catch (\Exception $ex) {
            $exception = $ex;
        } finally {
            $this->logger->info(
                "Saga {saga} is committing event {event}",
                [
                    'saga' => $sagaId,
                    'event' => $event->getPayloadType()
                ]
            );
            $this->commit($saga);
        }

        if (null !== $exception) {
            $this->handleInvokationException($exception, $event, $saga);
        }

        return $saga;
    }

    private function doInvokeSaga(
        EventMessageInterface $event,
        SagaInterface $saga
    ) {
        try {
            CorrelationDataHolder::setCorrelationData($this->correlationDataProvider->correlationDataFor($event));
            $saga->handle($event);
        } catch (\RuntimeException $ex) {
            $this->handleInvokationException($ex, $event, $saga);
        }
    }

    private function handleInvokationException(
        \Exception $ex,
        EventMessageInterface $event,
        SagaInterface $saga
    ) {
        if ($this->suppressExceptions) {
            $this->logger->error(
                "An exception occurred while a Saga {name} was handling an Event {event}: {exception}",
                [
                    'name' => get_class($saga),
                    'event' => $event->getPayloadType(),
                    'exception' => $ex->getMessage()
                ]
            );
        } else {
            throw $ex;
        }
    }

    /**
     * Commits the given <code>saga</code> to the registered repository.
     *
     * @param SagaInterface $saga the Saga to commit.
     */
    protected function commit(SagaInterface $saga)
    {
        $this->sagaRepository->commit($saga);
    }

    /**
     * Perform pre-processing of sagas that have been newly created or have been loaded from the repository. This
     * method is invoked prior to invocation of the saga instance itself.
     *
     * @param SagaInterface $saga The saga instance for preprocessing
     */
    protected function preProcessSaga(SagaInterface $saga)
    {

    }

    /**
     * Returns the Saga Initialization Policy for a Saga of the given <code>sagaType</code> and <code>event</code>.
     * This policy provides the conditions to create new Saga instance, as well as the initial association of that
     * saga.
     *
     * @param string $sagaType The type of Saga to get the creation policy for
     * @param EventMessageInterface $event The Event that is being dispatched to Saga instances
     * @return SagaInitializationPolicy the initialization policy for the Saga
     */
    abstract protected function getSagaCreationPolicy(
        $sagaType,
        EventMessageInterface $event
    );

    /**
     * Extracts the AssociationValues from the given <code>event</code> as relevant for a Saga of given
     * <code>sagaType</code>. A single event may be associated with multiple values.
     *
     * @param string $sagaType The type of Saga about to handle the Event
     * @param EventMessageInterface $event The event containing the association information
     * @return array the AssociationValues indicating which Sagas should handle given event
     */
    abstract protected function extractAssociationValues(
        $sagaType,
        EventMessageInterface $event
    );

    /**
     * Sets whether or not to suppress any exceptions that are cause by invoking Sagas. When suppressed, exceptions are
     * logged. Defaults to <code>true</code>.
     *
     * @param boolean $suppressExceptions whether or not to suppress exceptions from Sagas.
     */
    public function setSuppressExceptions($suppressExceptions)
    {
        $this->suppressExceptions = $suppressExceptions;
    }


    /**
     * @param LoggerInterface $logger
     * @return null
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return array
     */
    public function getManagedSagaTypes()
    {
        return $this->sagaTypes;
    }

    /**
     * Sets the correlation data provider for this SagaManager. It will provide the data to attach to messages sent by
     * Sagas managed by this manager.
     *
     * @param CorrelationDataProviderInterface $correlationDataProvider the correlation data provider for this SagaManager
     */
    public function setCorrelationDataProvider(CorrelationDataProviderInterface $correlationDataProvider)
    {
        $this->correlationDataProvider = $correlationDataProvider;
    }

    /**
     * Sets the given <code>correlationDataProviders</code>. Each will provide data to attach to messages sent by Sagas
     * managed by this manager. When multiple providers provide different values for the same key, the latter provider
     * will overwrite any values set earlier.
     *
     * @param array $correlationDataProviders the correlation data providers for this SagaManager
     */
    public function setCorrelationDataProviders(array $correlationDataProviders)
    {
        $this->correlationDataProvider = new MultiCorrelationDataProvider($correlationDataProviders);
    }

}
