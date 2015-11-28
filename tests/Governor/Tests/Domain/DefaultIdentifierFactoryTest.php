<?php
/**
 * This file is part of the SmartGecko(c) business platform.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Governor\Tests\Domain;

use Ramsey\Uuid\Uuid;
use Governor\Framework\Domain\DefaultIdentifierFactory;

/**
 * DefaultIdentifierFactory unit tests
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
class DefaultIdentifierFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testGenerate()
    {
        $factory = new DefaultIdentifierFactory();

        $this->assertTrue(Uuid::isValid($factory->generateIdentifier()));
    }

    public function testInstance()
    {
        $this->assertInstanceOf(DefaultIdentifierFactory::class, DefaultIdentifierFactory::getInstance());
    }
}