<?php
/**
 * This file is part of the SmartGecko(c) business platform.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Governor\Framework\EventStore\Mongo\Criteria;


/**
 * Representation of an AND operator for Mongo selection criteria.
 *
 * @author Allard Buijze
 * @since 2.0
 */
class AndX extends MongoCriteria {

    /**
     * @var MongoCriteria
     */
    private $criteria1;
    /**
     * @var MongoCriteria
     */
    private $criteria2;

    /**
     * Returns a criterium that requires both <code>criteria1</code> and <code>criteria2</code>  to be
     * <code>true</code>.
     *
     * @param MongoCriteria $criteria1 One of the criteria that must evaluate to true
     * @param MongoCriteria $criteria2 One of the criteria that must evaluate to true
     */
    public function __construct(MongoCriteria $criteria1, MongoCriteria $criteria2)
    {
        $this->criteria1 = $criteria1;
        $this->criteria2 = $criteria2;
    }


    public function asMongoObject() {
        return [
            '$and' => [
                $this->criteria1->asMongoObject(),
                $this->criteria2->asMongoObject()
            ]
        ];
    }
}