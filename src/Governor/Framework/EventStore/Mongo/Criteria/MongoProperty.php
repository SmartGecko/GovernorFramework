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

namespace Governor\Framework\EventStore\Mongo\Criteria;

use Governor\Framework\EventStore\Management\PropertyInterface;


/**
 * Property implementation for use by the Mongo Event Store.
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
class MongoProperty implements PropertyInterface
{

    private $propertyName;

    /**
     * Initialize a property for the given <code>propertyName</code>.
     *
     * @param string $propertyName The name of the property of the Mongo document.
     */
    public function __construct($propertyName)
    {
        $this->propertyName = $propertyName;
    }


    public function lessThan($expression)
    {
        return new SimpleMongoOperator($this, '$lt', $expression);
    }


    public function lessThanEquals($expression)
    {
        return new SimpleMongoOperator($this, '$lte', $expression);
    }


    public function greaterThan($expression)
    {
        return new SimpleMongoOperator($this, '$gt', $expression);
    }


    public function greaterThanEquals($expression)
    {
        return new SimpleMongoOperator($this, '$gte', $expression);
    }


    public function is($expression)
    {
        return new Equals($this, $expression);
    }


    public function isNot($expression)
    {
        return new SimpleMongoOperator($this, '$ne', $expression);
    }


    public function in($expression)
    {
        return new CollectionCriteria($this, '$in', $expression);
    }


    public function notIn($expression)
    {
        return new CollectionCriteria($this, '$nin', $expression);
    }

    /**
     * Returns the name of the property.
     *
     * @return string the name of the property
     */
    public function getName()
    {
        return $this->propertyName;
    }
}