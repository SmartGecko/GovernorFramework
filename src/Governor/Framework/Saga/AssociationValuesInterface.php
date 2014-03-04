<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Saga;

/**
 * Interface describing a container of {@link AssociationValue Association Values} for a single {@link Saga} instance.
 * This container tracks changes made to its contents between commits (see {@link #commit()}).
 */
interface AssociationValuesInterface
{

    /**
     * Returns the Set of association values that have been removed since the last {@link #commit()}.
     * <p/>
     * If an association was added and then removed (or vice versa), without any calls to {@link #commit()} in
     * between, it is not returned.
     *
     * @return the Set of association values removed since the last {@link #commit()}.
     */
    public function removedAssociations();

    /**
     * Returns the Set of association values that have been added since the last  {@link #commit()}.
     * <p/>
     * If an association was added and then removed (or vice versa), without any calls to {@link #commit()} in
     * between, it is not returned.
     *
     * @return the Set of association values added since the last {@link #commit()}.
     */
    public function addedAssociations();

    /**
     * Resets the tracked changes.
     */
    public function commit();

    /**
     * Returns the number of AssociationValue instances available in this container
     *
     * @return the number of AssociationValue instances available
     */
    public function size();

    /**
     * Indicates whether this instance contains the given <code>associationValue</code>.
     *
     * @param associationValue the association value to verify
     * @return <code>true</code> if the association value is available in this instance, otherwise <code>false</code>
     */
    public function contains(AssociationValue $associationValue);

    /**
     * Adds the given <code>associationValue</code>, if it has not been previously added.
     * <p/>
     * When added (method returns <code>true</code>), the given <code>associationValue</code> will be returned on the
     * next call to {@link #addedAssociations()}, unless it has been removed after the last call to {@link
     * #removedAssociations()}.
     *
     * @param associationValue The association value to add
     * @return <code>true</code> if the value was added, <code>false</code> if it was already contained in this
     *         instance
     */
    public function add(AssociationValue $associationValue);

    /**
     * Removes the given <code>associationValue</code>, if it is contained by this instance.
     * <p/>
     * When removed (method returns <code>true</code>), the given <code>associationValue</code> will be returned on the
     * next call to {@link #removedAssociations()}, unless it has been added after the last call to {@link
     * #addedAssociations()}.
     *
     * @param associationValue The association value to remove
     * @return <code>true</code> if the value was removed, <code>false</code> if it was not contained in this instance
     */
    public function remove(AssociationValue $associationValue);
}
