<?php
/**
 * This file is part of the SmartGecko(c) business platform.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Governor\Tests\EventStore\Mongo\Criteria;

use Governor\Framework\EventStore\Mongo\Criteria\MongoProperty;

class CollectionCriteriaTest extends \PHPUnit_Framework_TestCase
{
    public function testValueInCollection()
    {
        $collection = ['first', 'second'];
        $actual = (new MongoProperty('prop'))->in($collection);

        $this->assertEquals(
            [
                'prop' => [
                    '$in' => [
                        'first',
                        'second'
                    ]
                ]
            ],
            $actual->asMongoObject()
        );
    }

    public function testValueInString()
    {

        $actual = (new MongoProperty("prop"))->in('collection');

        $this->assertEquals(
            [
                'prop' => [
                    '$in' => 'collection'
                ]
            ],
            $actual->asMongoObject()
        );
    }

    public function testValueNotInCollection()
    {
        $collection = ['first', 'second'];
        $actual = (new MongoProperty('prop'))->notIn($collection);

        $this->assertEquals(
            [
                'prop' => [
                    '$nin' => [
                        'first',
                        'second'
                    ]
                ]
            ],
            $actual->asMongoObject()
        );
    }

}