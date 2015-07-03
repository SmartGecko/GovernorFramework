<?php
/**
 * This file is part of the SmartGecko(c) business platform.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Governor\Tests\EventStore\Mongo\Criteria;

use Governor\Framework\EventStore\Mongo\Criteria\MongoCriteria;

class StubMongoCriteria extends MongoCriteria
{
    /**
     * @var array
     */
    private $mongoObject;

    public function __construct(array $mongoObject)
    {
        $this->mongoObject = $mongoObject;
    }


    /**
     * @return array
     */
    public function asMongoObject()
    {
        return $this->mongoObject;
    }

}