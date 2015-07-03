<?php
/**
 * This file is part of the SmartGecko(c) business platform.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Governor\Framework\EventStore\Mongo\Criteria;

use Governor\Framework\EventStore\Management\CriteriaBuilderInterface;


/**
 * The CriteriaBuilder implementation for use with the Mongo event store.
 *
 * @author Allard Buijze
 * @since 2.0
 */
class MongoCriteriaBuilder implements CriteriaBuilderInterface
{

    public function property($propertyName)
    {
        return new MongoProperty($propertyName);
    }
}