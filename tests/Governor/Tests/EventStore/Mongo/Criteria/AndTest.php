<?php
/**
 * This file is part of the SmartGecko(c) business platform.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Governor\Tests\EventStore\Mongo\Criteria;


use Governor\Framework\EventStore\Mongo\Criteria\AndX;

class AndTest extends \PHPUnit_Framework_TestCase
{
    public function testAndOperator()
    {
        $criteria1 = new StubMongoCriteria(['a1' => 'b']);
        $criteria2 = new StubMongoCriteria(['a2' => 'b']);

        $actual = new AndX($criteria1, $criteria2);

        $this->assertEquals(
            [
                '$and' => [
                    ['a1' => 'b'],
                    ['a2' => 'b']
                ]
            ],
            $actual->asMongoObject()
        );
    }
}