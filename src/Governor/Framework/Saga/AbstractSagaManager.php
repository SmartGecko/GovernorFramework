<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Saga;

use Governor\Framework\Domain\EventMessageInterface;

/**
 * Description of AbstractSagaManager
 *
 * @author david
 */
abstract class AbstractSagaManager implements SagaManagerInterface
{

    private $sagaRepository;
    private $sagaFactory;
    private $sagaTypes = array();
    private $suppressExceptions = true;
    private $synchronizeSagaAccess = true;
    private $sagasInCreation = array();

    //private final IdentifierBasedLock lock = new IdentifierBasedLock();
    //private Map<String, Saga> sagasInCreation = new ConcurrentHashMap<String, Saga>();

    public function __construct(SagaRepositoryInterface $sagaRepository,
            SagaFactoryInterface $sagaFactory, array $sagaTypes = array())
    {
        $this->sagaRepository = $sagaRepository;
        $this->sagaFactory = $sagaFactory;
        $this->sagaTypes = $sagaTypes;
    }

    public function getTargetType()
    {
        
    }

    public function handle(EventMessageInterface $event)
    {
        echo "HANDLE\n";
        foreach ($this->sagaTypes as $sagaType) {
            $associationValues = $this->extractAssociationValues($sagaType,
                    $event);

            if (null !== $associationValues && !empty($associationValues)) {
                $sagaOfTypeInvoked = $this->invokeExistingSagas($event,
                        $sagaType, $associationValues);
                $initializationPolicy = $this->getSagaCreationPolicy($sagaType,
                        $event);
                if ($initializationPolicy->getCreationPolicy() === SagaCreationPolicy::ALWAYS
                        || (!$sagaOfTypeInvoked && $initializationPolicy->getCreationPolicy()
                        === SagaCreationPolicy::IF_NONE_FOUND)) {
                    $this->startNewSaga($event, $sagaType,
                            $initializationPolicy->getInitialAssociationValue());
                }
            }
        }
    }

    private function containsAny(AssociationValuesInterface $associationValues,
            array $toFind)
    {
        foreach ($toFind as $valueToFind) {
            if ($associationValues->contains($valueToFind)) {
                return true;
            }
        }
        return false;
    }

    private function startNewSaga(EventMessageInterface $event, $sagaType,
            AssociationValue $associationValue)
    {
        $newSaga = $this->sagaFactory->createSaga($sagaType);
        $newSaga->associateWith($associationValue);
        $this->preProcessSaga($newSaga);
        $this->sagasInCreation[$newSaga->getSagaIdentifier()] = $newSaga;

        try {
            $this->doInvokeSaga($event, $newSaga);
        } finally {
            $this->sagaRepository->add($newSaga);
        }
    }

    private function invokeExistingSagas(EventMessageInterface $event,
            $sagaType, $associationValues)
    {
        $sagas = array();

        foreach ($associationValues as $associationValue) {
            $sagas = $this->sagaRepository->find($sagaType, $associationValue);
        }

      /*  foreach ($this->sagasInCreation as $id => $sagaInCreation) {
            if ($sagaType === get_class($sagaInCreation) && $this->containsAny($sagaInCreation->getAssociationValues(),
                            $associationValues)) {
                $sagas[] = $id;
            }
        }*/

        $sagaOfTypeInvoked = false;        

        foreach ($sagas as $sagaId) {
            $this->loadAndInvoke($event, $sagaId, $associationValues);
        }

        return $sagaOfTypeInvoked;
    }

    private function loadAndInvoke(EventMessageInterface $event, $sagaId,
            array $associations)
    {
        $saga = $this->sagaRepository->load($sagaId);

        if (null === $saga || !$saga->isActive() || !$this->containsAny($saga->getAssociationValues(),
                        $associations)) {
            return null;
        }

        $this->preProcessSaga($saga);
        $exception = null;

        try {
            $saga->handle($event);
        } catch (\Exception $ex) {

            $exception = $ex;
        } finally {
            $this->commit($saga);
        }

        if (null !== $exception) {
            if ($this->suppressExceptions) {
                /*    logger.error(format("An exception occurred while a Saga [%s] was handling an Event [%s]:",
                  saga.getClass().getSimpleName(),
                  event.getPayloadType().getSimpleName()),
                  e); */
            } else {
                throw $exception;
            }
        }

        return $saga;
    }

    private function doInvokeSaga(EventMessageInterface $event,
            SagaInterface $saga)
    {
        try {
            $saga->handle($event);
        } catch (\RuntimeException $ex) {
            if ($this->suppressExceptions) {
                //    logger.error(format("An exception occurred while a Saga [%s] was handling an Event [%s]:",
                //                     saga.getClass().getSimpleName(),
                //             event.getPayloadType().getSimpleName()),
                //      e);
            } else {
                throw $ex;
            }
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
     * @param EventMessageInterface $event    The Event that is being dispatched to Saga instances
     * @return SagaInitializationPolicy the initialization policy for the Saga
     */
    protected abstract function getSagaCreationPolicy($sagaType,
            EventMessageInterface $event);

    /**
     * Extracts the AssociationValues from the given <code>event</code> as relevant for a Saga of given
     * <code>sagaType</code>. A single event may be associated with multiple values.
     *
     * @param string $sagaType The type of Saga about to handle the Event
     * @param EventMessageInterface $event    The event containing the association information
     * @return array the AssociationValues indicating which Sagas should handle given event
     */
    protected abstract function extractAssociationValues($sagaType,
            EventMessageInterface $event);

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

}
