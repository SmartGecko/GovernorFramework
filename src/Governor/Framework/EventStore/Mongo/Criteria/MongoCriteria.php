<?php
/**
 * This file is part of the SmartGecko(c) business platform.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Governor\Framework\EventStore\Mongo\Criteria;

use Governor\Framework\EventStore\Management\CriteriaInterface;


/**
 * Abstract class for Mongo-based criteria.
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
abstract class MongoCriteria implements CriteriaInterface
{


    public function andX(CriteriaInterface $criteria)
    {
        return new AndX($this, $criteria);
    }


    public function orX(CriteriaInterface $criteria)
    {
        return new OrX($this, $criteria);
    }

    /**
     * Returns the DBObject representing the criterium. This DBObject can be used to select documents in a Mongo Query.
     *
     * @return array the DBObject representing the criterium
     */
    public abstract function asMongoObject();


}