<?php
/**
 * This file is part of the SmartGecko(c) business platform.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Governor\Tests\EventStore\Mongo\Criteria;

use Governor\Framework\EventStore\Mongo\Criteria\Equals;
use Governor\Framework\EventStore\Mongo\Criteria\MongoProperty;

class EqualsTest extends \PHPUnit_Framework_TestCase
{


    public function testEqualsToValue()
    {
        $actual = (new Equals(new MongoProperty("property"), "someValue"))->asMongoObject();

        $this->assertArrayHasKey('property', $actual);
        $this->assertEquals('someValue', $actual['property']);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testEqualsBetweenProperties()
    {
        new Equals(new MongoProperty("property"), new MongoProperty("bla"));
    }
}