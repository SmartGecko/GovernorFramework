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

/**
 * Interface describing a container of {@link AssociationValue Association Values} for a single {@link Saga} instance.
 * This container tracks changes made to its contents between commits (see {@link #commit()}).
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
interface AssociationValuesInterface
{

    /**
     * Returns the Set of association values that have been removed since the last {@link #commit()}.
     * <p/>
     * If an association was added and then removed (or vice versa), without any calls to {@link #commit()} in
     * between, it is not returned.
     *
     * @return AssociationValue[] the Set of association values removed since the last {@link #commit()}.
     */
    public function removedAssociations();

    /**
     * Returns the Set of association values that have been added since the last  {@link #commit()}.
     * <p/>
     * If an association was added and then removed (or vice versa), without any calls to {@link #commit()} in
     * between, it is not returned.
     *
     * @return AssociationValue[] the Set of association values added since the last {@link #commit()}.
     */
    public function addedAssociations();

    /**
     * Resets the tracked changes.
     */
    public function commit();

    /**
     * Returns the number of AssociationValue instances available in this container
     *
     * @return integer the number of AssociationValue instances available
     */
    public function size();

    /**
     * Indicates whether this instance contains the given <code>associationValue</code>.
     *
     * @param AssociationValue $associationValue the association value to verify
     * @return boolean <code>true</code> if the association value is available in this instance, otherwise <code>false</code>
     */
    public function contains(AssociationValue $associationValue);

    /**
     * Adds the given <code>associationValue</code>, if it has not been previously added.
     * <p/>
     * When added (method returns <code>true</code>), the given <code>associationValue</code> will be returned on the
     * next call to {@link #addedAssociations()}, unless it has been removed after the last call to {@link
     * #removedAssociations()}.
     *
     * @param AssociationValue $associationValue The association value to add
     * @return boolean <code>true</code> if the value was added, <code>false</code> if it was already contained in this
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
     * @param AssociationValue $associationValue The association value to remove
     * @return boolean <code>true</code> if the value was removed, <code>false</code> if it was not contained in this instance
     */
    public function remove(AssociationValue $associationValue);

    /**
     * @return AssociationValue[]
     */
    public function asArray();
}
