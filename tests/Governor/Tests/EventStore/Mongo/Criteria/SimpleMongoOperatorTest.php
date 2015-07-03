<?php
/**
 * This file is part of the SmartGecko(c) business platform.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Governor\Tests\EventStore\Mongo\Criteria;

use Governor\Framework\EventStore\Mongo\Criteria\SimpleMongoOperator;
use Governor\Framework\EventStore\Mongo\Criteria\MongoProperty;

class SimpleMongoOperatorTest extends \PHPUnit_Framework_TestCase
{

    public function testSimpleOperator()
    {
        $dbObject = (new SimpleMongoOperator(new MongoProperty("prop"), '$bla', "value"))->asMongoObject();

        $this->assertEquals(
            [
                'prop' => ['$bla' => 'value']
            ],
            $dbObject
        );
    }
}