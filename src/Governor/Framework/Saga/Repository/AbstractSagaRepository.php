<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Saga\Repository;

use Governor\Framework\Saga\SagaInterface;
use Governor\Framework\Saga\SagaRepositoryInterface;
use Governor\Framework\Saga\AssociationValue;

/**
 * Abstract implementation for saga repositories.
 */
abstract class AbstractSagaRepository implements SagaRepositoryInterface
{

    public function find($type, AssociationValue $associationValue)
    {
        return $this->findAssociatedSagaIdentifiers($type, $associationValue);
    }

    public function add(SagaInterface $saga)
    {
        if ($saga->isActive()) {
            $sagaType = $this->typeOf($saga);
            $associationValues = $saga->getAssociationValues();

            foreach ($associationValues->addedAssociations() as $av) {
                $this->storeAssociationValue(
                    $av,
                    $sagaType,
                    $saga->getSagaIdentifier()
                );
            }

            $associationValues->commit();
            $this->storeSaga($saga);
        }
    }

    public function commit(SagaInterface $saga)
    {
        if (!$saga->isActive()) {
            $this->deleteSaga($saga);
        } else {
            $sagaType = $this->typeOf($saga);
            $associationValues = $saga->getAssociationValues();

            foreach ($associationValues->addedAssociations() as $av) {
                $this->storeAssociationValue(
                    $av,
                    $sagaType,
                    $saga->getSagaIdentifier()
                );
            }

            foreach ($associationValues->removedAssociations() as $av) {
                $this->removeAssociationValue(
                    $av,
                    $sagaType,
                    $saga->getSagaIdentifier()
                );
            }

            $associationValues->commit();
            $this->updateSaga($saga);
        }
    }

    /**
     * Finds the identifiers of the sagas of given <code>type</code> associated with the given
     * <code>associationValue</code>.
     *
     * @param string $type The type of saga to find identifiers for
     * @param AssociationValue $associationValue The value the saga must be associated with
     * @return array The identifiers of sagas associated with the given <code>associationValue</code>
     */
    protected abstract function findAssociatedSagaIdentifiers(
        $type,
        AssociationValue $associationValue
    );

    /**
     * Returns the type identifier to use for the given <code>sagaClass</code>. This information is typically provided
     * by the Serializer, if the repository stores serialized instances.
     *
     * @param mixed $sagaClass The type of saga to get the type identifier for.
     * @return string The type identifier to use for the given sagaClass
     */
    protected abstract function typeOf($sagaClass);

    /**
     * Remove the given saga as well as all known association values pointing to it from the repository. If no such
     * saga exists, nothing happens.
     *
     * @param SagaInterface $saga The saga instance to remove from the repository
     */
    protected abstract function deleteSaga(SagaInterface $saga);

    /**
     * Update a stored Saga, by replacing it with the given <code>saga</code> instance.
     *
     * @param SagaInterface $saga The saga that has been modified and needs to be updated in the storage
     */
    protected abstract function updateSaga(SagaInterface $saga);

    /**
     * Stores a newly created Saga instance.
     *
     * @param SagaInterface $saga The newly created Saga instance to store.
     */
    protected abstract function storeSaga(SagaInterface $saga);

    /**
     * Store the given <code>associationValue</code>, which has been associated with given <code>sagaIdentifier</code>.
     *
     * @param AssociationValue $associationValue The association value to store
     * @param string $sagaType Type type of saga the association value belongs to
     * @param string $sagaIdentifier The saga related to the association value
     */
    protected abstract function storeAssociationValue(
        AssociationValue $associationValue,
        $sagaType,
        $sagaIdentifier
    );

    /**
     * Removes the association value that has been associated with Saga, identified with the given
     * <code>sagaIdentifier</code>.
     *
     * @param AssociationValue $associationValue The value to remove as association value for the given saga
     * @param string $sagaType The type of the Saga to remove the association from
     * @param string $sagaIdentifier The identifier of the Saga to remove the association from
     */
    protected abstract function removeAssociationValue(
        AssociationValue $associationValue,
        $sagaType,
        $sagaIdentifier
    );
}
