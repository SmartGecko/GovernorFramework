<?php
/**
 * This file is part of the SmartGecko(c) business platform.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Governor\Framework\EventStore\Mongo\Criteria;


/**
 * Implementation of the simple Mongo Operators (those without special structural requirements), such as Less Than,
 * Less Than Equals, etc.
 *
 * @author Allard Buijze
 * @since 2.0
 */
class SimpleMongoOperator extends MongoCriteria
{
    /**
     * @var MongoProperty
     */
    private $property;
    /**
     * @var string
     */
    private $operator;
    /**
     * @var mixed
     */
    private $expression;

    /**
     * Initializes an criterium where the given <code>property</code>, <code>operator</code> and
     * <code>expression</code>
     * make a match. The expression may be a fixed value, as well as a MongoProperty
     *
     * @param MongoProperty $property The property to match
     * @param string $operator The operator to match with
     * @param mixed $expression The expression to match against the property
     */
    public function  __construct(MongoProperty $property, $operator, $expression)
    {
        $this->property = $property;
        $this->operator = $operator;
        $this->expression = $expression;

        if ($expression instanceof MongoProperty) {
            throw new \InvalidArgumentException(
                'The MongoEventStore does not support comparison between two properties'
            );
        }

    }


    public function asMongoObject()
    {
        return [$this->property->getName() => [$this->operator => (string)$this->expression]];
    }
}