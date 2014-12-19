<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Saga;

/**
 * Interface towards the storage mechanism of Saga instances. Saga Repositories can find sagas either through the
 * values
 * they have been associated with (see {@link AssociationValue}) or via their unique identifier.
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
interface SagaRepositoryInterface
{

    /**
     * Find saga instances of the given <code>type</code> that have been associated with the given
     * <code>associationValue</code>.
     * <p/>
     * Returned Sagas must be {@link #commit(Saga) committed} after processing.
     *
     * @param string $type             The type of Saga to return
     * @param AssociationValue $associationValue The value that the returned Sagas must be associated with
     * @return array An array containing the found Saga instances. If none are found, an empty Set is returned. Will never
     *         return <code>null</code>.
     */
    public function find($type, AssociationValue $associationValue);

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
    public function load($sagaIdentifier);

    /**
     * Commits the changes made to the Saga instance. At this point, the repository may release any resources kept for
     * this saga. If the committed saga is marked inActive ({@link org.axonframework.saga.Saga#isActive()} returns
     * {@code false}), the repository should delete the saga from underlying storage and remove all stored association
     * values associated with that Saga.
     * <p/>
     * Implementations *may* (temporarily) return a cached version of the Saga, which is marked inactive.
     *
     * @param SagaInterface $saga The Saga instance to commit
     */
    public function commit(SagaInterface $saga);

    /**
     * Registers a newly created Saga with the Repository. Once a Saga instance has been added, it can be found using
     * its association values or its unique identifier.
     * <p/>
     * Note that if the added Saga is marked inActive ({@link org.axonframework.saga.Saga#isActive()} returns
     * {@code false}), it is not stored.
     *
     * @param SagaInterface $saga The Saga instances to add.
     */
    public function add(SagaInterface $saga);
}
