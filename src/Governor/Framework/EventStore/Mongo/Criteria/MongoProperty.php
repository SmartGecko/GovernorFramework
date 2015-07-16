<?php
/**
 * This file is part of the SmartGecko(c) business platform.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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