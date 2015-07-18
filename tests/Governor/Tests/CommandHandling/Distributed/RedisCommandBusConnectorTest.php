<?php
/**
 * This file is part of the SmartGecko(c) business platform.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Governor\Tests\CommandHandling\Distributed;

use Governor\Framework\CommandHandling\Callbacks\ClosureCommandCallback;
use Governor\Framework\CommandHandling\CommandDispatchInterceptorInterface;
use Governor\Framework\CommandHandling\Distributed\CommandTimeoutException;
use Governor\Framework\CommandHandling\Distributed\RedisTemplate;
use Governor\Framework\CommandHandling\SimpleCommandBus;
use Governor\Framework\Serializer\JMSSerializer;
use Governor\Framework\CommandHandling\Distributed\RedisCommandBusConnector;
use Governor\Framework\UnitOfWork\DefaultUnitOfWorkFactory;
use Governor\Framework\CommandHandling\CommandMessageInterface;
use Governor\Framework\CommandHandling\CommandHandlerInterface;
use Governor\Framework\CommandHandling\GenericCommandMessage;
use Governor\Framework\UnitOfWork\UnitOfWorkInterface;
use Governor\Tests\CommandHandling\TestCommand;
use Governor\Tests\CommandHandling\AnotherTestCommand;

class RedisCommandBusConnectorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RedisCommandBusConnector
     */
    private $testSubject;

    /**
     * @var SimpleCommandBus
     */
    private $localSegment;

    /**
     * @var RedisTemplate
     */
    private $template;

    /**
     * @var JMSSerializer
     */
    private $serializer;

    public function setUp()
    {
        $this->serializer = new JMSSerializer();
        $this->localSegment = new SimpleCommandBus(new DefaultUnitOfWorkFactory());
        $this->template = new RedisTemplate('tcp://127.0.0.1:6379?read_write_timeout=-1', 'test-node1', []);
        $this->testSubject = new RedisCommandBusConnector($this->template, $this->localSegment, $this->serializer);
    }

    public function tearDown()
    {
        $this->template->getClient()->flushall();
    }

    /**
     * @expectedException \Governor\Framework\CommandHandling\NoHandlerForCommandException
     */
    public function testShouldThrowExceptionWhenNoHandlers()
    {
        $this->testSubject->send('key', GenericCommandMessage::asCommandMessage(new TestCommand('key')));
    }

    public function testRouteLocalCommands()
    {
        $this->localSegment->subscribe(TestCommand::class, new TestCommandHandler());
        $this->testSubject->saveSubscriptions();

        $interceptor = new DispatchInterceptor();
        $this->localSegment->setDispatchInterceptors([$interceptor]);

        $callback = new ClosureCommandCallback(
            function ($result) {
                $this->assertEquals('key', $result->getPayload()->getText());
            }, function ($error) {
            $this->fail('Exception not expected');
        }
        );

        $this->testSubject->send('key', GenericCommandMessage::asCommandMessage(new TestCommand('key')), $callback);
        $this->assertCount(1, $interceptor->commands);
        $this->assertEmpty($this->template->getPendingCommands());
    }

    public function testRemoteDispatchWithoutReply()
    {
        $this->localSegment->subscribe(TestCommand::class, new TestCommandHandler());
        $remoteTemplate = new RedisTemplate('tcp://127.0.0.1:6379?read_write_timeout=-1', 'test-node2', []);
        $remoteTemplate->subscribe(TestCommand::class);

        $interceptor = new DispatchInterceptor();
        $this->localSegment->setDispatchInterceptors([$interceptor]);

        $this->testSubject->send('key', GenericCommandMessage::asCommandMessage(new TestCommand('key')));
        $this->assertEmpty($interceptor->commands);
        $this->assertEmpty($remoteTemplate->getClient()->keys('governor:response:*'));
        $this->assertCount(1, $remoteTemplate->getPendingCommands());
    }

    public function testRemoteDispatchReplyTimeout()
    {
        $this->localSegment->subscribe(TestCommand::class, new TestCommandHandler());
        $remoteTemplate = new RedisTemplate('tcp://127.0.0.1:6379?read_write_timeout=-1', 'test-node2', []);
        $remoteTemplate->subscribe(TestCommand::class);

        $this->template->setTimeout(1);
        $interceptor = new DispatchInterceptor();
        $this->localSegment->setDispatchInterceptors([$interceptor]);

        $callback = new ClosureCommandCallback(
            function ($result) {
                $this->fail('Exception expected');
            }, function ($error) {
                $this->assertInstanceOf(CommandTimeoutException::class, $error);
        }
        );

        $this->testSubject->send('key', GenericCommandMessage::asCommandMessage(new TestCommand('key')), $callback);
        $this->assertEmpty($interceptor->commands);
        $this->assertEmpty($this->template->getClient()->keys('governor:response:*'));
        $this->assertCount(1, $this->template->getPendingCommands($remoteTemplate->getNodeName()));
    }
}

class DispatchInterceptor implements CommandDispatchInterceptorInterface
{
    /**
     * @var array
     */
    public $commands = [];

    /**
     * Invoked each time a command is about to be dispatched on a Command Bus. The given <code>commandMessage</code>
     * represents the command being dispatched.
     *
     * @param CommandMessageInterface $commandMessage The command message intended to be dispatched on the Command Bus
     * @return CommandMessageInterface the command message to dispatch on the Command Bus
     */
    public function dispatch(CommandMessageInterface $commandMessage)
    {
        $this->commands[] = $commandMessage;
        return $commandMessage;
    }

}

class TestCommandHandler implements CommandHandlerInterface
{

    public function handle(
        CommandMessageInterface $commandMessage,
        UnitOfWorkInterface $unitOfWork
    ) {
        return $commandMessage;
    }

}