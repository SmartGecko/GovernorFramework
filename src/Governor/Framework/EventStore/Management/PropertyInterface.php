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

namespace Governor\Framework\EventStore\Management;

/**
 * Represents a property of the Domain Event entry stored by an Event Store. Typically, these properties must be the
 * "indexed" values, such as timeStamp, aggregate identifier, etc.
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a> 
 */
interface PropertyInterface {

    /**
     * Returns a criteria instance where the property must be "less than" the given <code>expression</code>. Some event
     * stores also allow the given expression to be a property.
     *
     * @param mixed $expression The expression to match against the property
     * @return CriteriaInterface a criteria instance describing a "less than" requirement.
     */
    public function lessThan($expression);

    /**
     * Returns a criteria instance where the property must be "less than" or "equal to" the given
     * <code>expression</code>. Some event stores also allow the given expression to be a property.
     *
     * @param mixed $expression The expression to match against the property
     * @return CriteriaInterface a criteria instance describing a "less than or equals" requirement.
     */
    public function lessThanEquals($expression);

    /**
     * Returns a criteria instance where the property must be "greater than" the given <code>expression</code>. Some
     * event stores also allow the given expression to be a property.
     *
     * @param mixed $expression The expression to match against the property
     * @return CriteriaInterface a criteria instance describing a "greater than" requirement.
     */
    public function greaterThan($expression);

    /**
     * Returns a criteria instance where the property must be "greater than" or "equal to" the given
     * <code>expression</code>. Some event stores also allow the given expression to be a property.
     *
     * @param mixed $expression The expression to match against the property
     * @return CriteriaInterface a criteria instance describing a "greater than or equals" requirement.
     */
    public function greaterThanEquals($expression);

    /**
     * Returns a criteria instance where the property must "equal" the given <code>expression</code>. Some event stores
     * also allow the given expression to be a property.
     *
     * @param mixed $expression The expression to match against the property
     * @return CriteriaInterface a criteria instance describing an "equals" requirement.
     */
    public function is($expression);

    /**
     * Returns a criteria instance where the property must be "not equal to" the given <code>expression</code>. Some
     * event stores also allow the given expression to be a property.
     *
     * @param mixed $expression The expression to match against the property
     * @return CriteriaInterface a criteria instance describing a "not equals" requirement.
     */
    public function isNot($expression);

    /**
     * Returns a criteria instance where the property must be "in" the given <code>expression</code>. Some event stores
     * also allow the given expression to be a property.
     * <p/>
     * Note that the given <code>expression</code> must describe a collection of some sort.
     *
     * @param mixed $expression The expression to match against the property
     * @return CriteriaInterface a criteria instance describing a "is in" requirement.
     */
    public function in($expression);

    /**
     * Returns a criteria instance where the property must be "not in" the given <code>expression</code>. Some event
     * stores also allow the given expression to be a property.
     *
     * @param mixed $expression The expression to match against the property
     * @return CriteriaInterface a criteria instance describing a "is not in" requirement.
     */
    public function notIn($expression);
}
