<?php
/**
 * This file is part of the SmartGecko(c) business platform.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Governor\Framework\EventStore\Mongo\Criteria;


/**
 * Implementation of Collection operators for the Mongo Criteria, such as "In" and "NotIn".
 *
 * @author Allard Buijze
 * @since 2.0
 */
class CollectionCriteria extends MongoCriteria
{

    /**
     * @var MongoProperty
     */
    private $property;
    /**
     * @var mixed
     */
    private $expression;
    /**
     * @var string
     */
    private $operator;

    /**
     * Returns a criterion that requires the given <code>property</code> value to be present in the given
     * <code>expression</code> to evaluate to <code>true</code>.
     *
     * @param MongoProperty $property The property to match
     * @param string $operator The collection operator to use
     * @param mixed $expression The expression to that expresses the collection to match against the property
     */
    public function __construct(MongoProperty $property, $operator, $expression)
    {
        $this->property = $property;
        $this->expression = $expression;
        $this->operator = $operator;
    }


    public function asMongoObject()
    {
        return [
            $this->property->getName() => [
                $this->operator => $this->expression
            ]
        ];

    }
}