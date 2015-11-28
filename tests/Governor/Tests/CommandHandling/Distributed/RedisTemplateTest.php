<?php
/**
 * This file is part of the SmartGecko(c) business platform.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Governor\Tests\CommandHandling\Distributed;

use Ramsey\Uuid\Uuid;
use Governor\Framework\CommandHandling\Distributed\RedisTemplate;

class RedisTemplateTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RedisTemplate
     */
    private $testSubject;

    public function setUp()
    {
        $this->testSubject = new RedisTemplate('tcp://127.0.0.1:6379?read_write_timeout=-1', 'test-node1', []);
    }

    public function tearDown()
    {
        $this->testSubject->getClient()->flushall();
    }

    public function testSubscribe()
    {
        $this->testSubject->subscribe('commandOne');
        $this->testSubject->subscribe('commandTwo');

        $anotherNode = new RedisTemplate('tcp://127.0.0.1:6379?read_write_timeout=-1', 'test-node2', []);
        $anotherNode->subscribe('commandTwo');

        $this->assertCount(1, $this->testSubject->getSubscriptions('commandOne'));
        $secondMembers = $this->testSubject->getSubscriptions('commandTwo');

        $this->assertCount(2, $secondMembers);
        $this->assertContains('test-node1', $secondMembers);
        $this->assertContains('test-node2', $secondMembers);
    }

    public function testUnsubscribe()
    {
        $this->testSubject->subscribe('commandOne');
        $this->testSubject->subscribe('commandTwo');

        $anotherNode = new RedisTemplate('tcp://127.0.0.1:6379?read_write_timeout=-1', 'test-node2', []);
        $anotherNode->subscribe('commandTwo');

        $this->testSubject->unsubscribe('commandOne');
        $this->testSubject->unsubscribe('commandTwo');

        $this->assertEmpty($this->testSubject->getSubscriptions('commandOne'));

        $secondMembers = $this->testSubject->getSubscriptions('commandTwo');

        $this->assertCount(1, $secondMembers);
        $this->assertContains('test-node2', $secondMembers);
    }

    public function testCommandReply()
    {
        $commandId1 = Uuid::uuid1()->toString();
        $commandId2 = Uuid::uuid1()->toString();

        $this->testSubject->writeCommandReply($commandId1, $commandId1);

        $data = $this->testSubject->readCommandReply($commandId1);
        $this->assertEquals($commandId1, $data[1]);

        $data = $this->testSubject->readCommandReply($commandId2, 1);
        $this->assertNull($data);
    }
}