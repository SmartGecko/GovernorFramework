<?php
/**
 * This file is part of the SmartGecko(c) business platform.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Governor\Tests\EventHandling;

use Governor\Framework\Domain\GenericEventMessage;
use Governor\Framework\EventHandling\EventBusInterface;
use Governor\Framework\EventHandling\DefaultClusterTerminal;

class DefaultClusterTerminalTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DefaultClusterTerminal
     */
    private $testSubject;

    public function setUp()
    {
        $this->testSubject = new DefaultClusterTerminal();
    }

    public function testPublishIsHandledByAllEventBuses()
    {
        $eventBus1 = $this->getMock(EventBusInterface::class);
        $eventBus2 = $this->getMock(EventBusInterface::class);
        $eventBus3 = $this->getMock(EventBusInterface::class);

        $this->testSubject->onEventBusSubscribed($eventBus1);
        $this->testSubject->onEventBusSubscribed($eventBus2);
        $this->testSubject->onEventBusSubscribed($eventBus3);

        $eventBus1->expects($this->once())
            ->method('publish');

        $eventBus2->expects($this->once())
            ->method('publish');

        $eventBus3->expects($this->once())
            ->method('publish');

        $this->testSubject->publish([GenericEventMessage::asEventMessage(new \stdClass())]);
    }
}