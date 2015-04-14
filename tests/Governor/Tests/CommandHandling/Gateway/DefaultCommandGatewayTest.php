<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Tests\CommandHandling\Gateway;

use Governor\Framework\CommandHandling\CommandBusInterface;
use Governor\Framework\CommandHandling\Gateway\DefaultCommandGateway;
use Governor\Framework\Correlation\CorrelationDataHolder;

/**
 * Unit tests for the DefaultCommandGateway
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
class DefaultCommandGatewayTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var DefaultCommandGateway
     */
    private $testSubject;

    /**
     * @var CommandBusInterface|\Phake_IMock
     */
    private $mockCommandBus;


    public function setUp()
    {
        CorrelationDataHolder::clear();
        $this->mockCommandBus = \Phake::mock(CommandBusInterface::class);
        $this->testSubject = new DefaultCommandGateway($this->mockCommandBus);
    }

    public function tearDown()
    {
        CorrelationDataHolder::clear();
    }

    public function testSend()
    {
        $command = new HelloCommand('Hi !!!');

        $this->testSubject->send($command);

        \Phake::verify($this->mockCommandBus)->dispatch(\Phake::anyParameters());
    }

    public function testSendAndWait()
    {
        $command = new HelloCommand('Hi !!!');

        $this->testSubject->sendAndWait($command);

        \Phake::verify($this->mockCommandBus)->dispatch(\Phake::anyParameters());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testSendAndWaitException()
    {
        $command = new HelloCommand('Hi !!!');

        \Phake::when($this->mockCommandBus)->dispatch(\Phake::anyParameters())->thenThrow(
            new \RuntimeException("exception")
        );

        $this->testSubject->sendAndWait($command);
    }

    public function testCorrelationDataIsAttached()
    {
        CorrelationDataHolder::setCorrelationData(array('correlationId' => 'test'));
        $this->testSubject->send(new HelloCommand('Hi !!!'));

        \Phake::verify($this->mockCommandBus)->dispatch(
            \Phake::capture($command),
            null
        );

        $this->assertEquals(array('correlationId' => 'test'), $command->getMetaData()->all());
    }

}

class HelloCommand
{

    private $message;

    function __construct($message)
    {
        $this->message = $message;
    }

    public function getMessage()
    {
        return $this->message;
    }

}
