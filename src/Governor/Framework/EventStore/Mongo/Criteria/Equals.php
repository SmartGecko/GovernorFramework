<?php
/**
 * This file is part of the SmartGecko(c) business platform.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Governor\Framework\EventStore\Mongo\Criteria;


/**
 * Representation of an Equals operator for Mongo selection criteria.
 *
 * @author Allard Buijze
 * @since 2.0
 */
class Equals extends MongoCriteria
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
     * Creates an equal instance that requires the given property to equal the given <code>expression</code>. The
     * expression may be either a fixed value, or another MongoProperty.
     *
     * @param MongoProperty $property The property to evaluate
     * @param mixed $expression The expression to compare the property with
     */
    public function __construct(MongoProperty $property, $expression)
    {
        if ($expression instanceof MongoProperty) {
            throw new \InvalidArgumentException(
                'The MongoEventStore does not support comparison between two properties'
            );
        }

        $this->property = $property;
        $this->expression = $expression;
    }


    public function asMongoObject()
    {
        return [
            $this->property->getName() => (string)$this->expression
        ];

    }
}