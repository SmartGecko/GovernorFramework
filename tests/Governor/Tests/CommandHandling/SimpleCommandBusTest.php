<?php

/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * The software is based on the Axon Framework project which is
 * licensed under the Apache 2.0 license. For more information on the Axon Framework
 * see <http://www.axonframework.org/>.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.governor-framework.org/>.
 */

namespace Governor\Tests\CommandHandling;

use Governor\Framework\CommandHandling\CommandHandlerInterceptorInterface;
use Governor\Framework\CommandHandling\CommandMessageInterface;
use Governor\Framework\CommandHandling\Callbacks\ClosureCommandCallback;
use Governor\Framework\CommandHandling\GenericCommandMessage;
use Governor\Framework\CommandHandling\SimpleCommandBus;
use Governor\Framework\CommandHandling\CommandHandlerInterface;
use Governor\Framework\UnitOfWork\CurrentUnitOfWork;
use Governor\Framework\UnitOfWork\DefaultUnitOfWorkFactory;
use Governor\Framework\UnitOfWork\UnitOfWorkInterface;
use Governor\Framework\CommandHandling\InterceptorChainInterface;

/**
 * SimpleCommandBus tests.
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
class SimpleCommandBusTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var SimpleCommandBus
     */
    private $commandBus;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->commandBus = new SimpleCommandBus(new DefaultUnitOfWorkFactory());
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {

    }

    /**
     * @expectedException \Governor\Framework\CommandHandling\NoHandlerForCommandException
     */
    public function testUnsubscribeAfterSubscribe()
    {
        $commandHandler = $this->getMock(CommandHandlerInterface::class);
        $this->commandBus->subscribe(
            get_class(new TestCommand('hi')),
            $commandHandler
        );
        $this->commandBus->unsubscribe(
            get_class(new TestCommand('hi')),
            $commandHandler
        );

        $this->commandBus->findCommandHandlerFor(GenericCommandMessage::asCommandMessage(new TestCommand('hi')));
    }


    public function testSubscribe()
    {
        $commandHandler = $this->getMock(CommandHandlerInterface::class);
        $this->commandBus->subscribe(
            get_class(new TestCommand('hi')),
            $commandHandler
        );

        $handler = $this->commandBus->findCommandHandlerFor(GenericCommandMessage::asCommandMessage(new TestCommand('hi')));

        $this->assertInstanceOf(CommandHandlerInterface::class, $handler);
    }

    /**
     * @expectedException \Governor\Framework\CommandHandling\NoHandlerForCommandException
     */
    public function testDispatchCommand_NoHandlerSubscribed()
    {
        $this->commandBus->dispatch(GenericCommandMessage::asCommandMessage(new TestCommand('hi')));
    }


    public function testDispatchCommand_HandlerSubscribed()
    {
        $commandHandler = new TestCommandHandler();
        $this->commandBus->subscribe(TestCommand::class, $commandHandler);

        $command = new TestCommand('hi');

        $this->commandBus->dispatch(
            GenericCommandMessage::asCommandMessage($command),
            new ClosureCommandCallback(
                function ($result) use ($command) {
                    $this->assertEquals($command, $result->getPayload());
                },
                function ($exception) {
                    $this->fail('Exception not expected');
                }
            )
        );
    }

    public function testDispatchCommand_ImplicitUnitOfWorkIsCommittedOnReturnValue()
    {
        //$spyUnitOfWorkFactory = spy(new DefaultUnitOfWorkFactory());
        $command = new TestCommand("Say hi!");
        $test = $this;

        $this->commandBus->subscribe(
            TestCommand::class,
            new CallbackCommandHandler(
                function (
                    CommandMessageInterface $commandMessage,
                    UnitOfWorkInterface $unitOfWork
                ) use ($test) {
                    $test->assertTrue(CurrentUnitOfWork::isStarted());
                    $test->assertTrue($unitOfWork->isStarted());
                    $test->assertNotNull(CurrentUnitOfWork::get());
                    $test->assertNotNull($unitOfWork);
                    $test->assertSame(CurrentUnitOfWork::get(), $unitOfWork);

                    return $commandMessage;
                }
            )
        );

        $callback = new ClosureCommandCallback(
            function ($result) use ($command) {
                $this->assertEquals($command, $result->getPayload());
            },
            function ($exception) {
                $this->fail("Did not expect exception");
            }
        );


        $this->commandBus->dispatch(
            GenericCommandMessage::asCommandMessage($command),
            $callback
        );
        //verify(spyUnitOfWorkFactory).createUnitOfWork();
        $this->assertFalse(CurrentUnitOfWork::isStarted());
    }

    public function testDispatchCommand_ImplicitUnitOfWorkIsRolledBackOnException()
    {
        $command = new TestCommand("Say hi!");
        $test = $this;

        $this->commandBus->subscribe(
            TestCommand::class,
            new CallbackCommandHandler(
                function (
                    CommandMessageInterface $commandMessage,
                    UnitOfWorkInterface $unitOfWork
                ) use ($test) {
                    $test->assertTrue(CurrentUnitOfWork::isStarted());
                    $test->assertNotNull(CurrentUnitOfWork::get());

                    throw new \RuntimeException("exception");
                }
            )
        );

        $callback = new ClosureCommandCallback(
            function ($result) use ($command) {
                $this->fail("Did not expect exception");
            },
            function ($exception) {
                $this->assertEquals(\RuntimeException::class, get_class($exception));
            }
        );

        $this->commandBus->dispatch(
            GenericCommandMessage::asCommandMessage($command),
            $callback
        );

        $this->assertFalse(CurrentUnitOfWork::isStarted());
    }

    public function testUnsubscribe_HandlerNotKnown()
    {
        $this->commandBus->unsubscribe(
            TestCommand::class,
            new TestCommandHandler()
        );
    }

    /**
     * @Test
     * public void testDispatchCommand_UnitOfWorkIsCommittedOnCheckedException() {
     * UnitOfWorkFactory mockUnitOfWorkFactory = mock(DefaultUnitOfWorkFactory.class);
     * UnitOfWork mockUnitOfWork = mock(UnitOfWork.class);
     * when(mockUnitOfWorkFactory.createUnitOfWork()).thenReturn(mockUnitOfWork);
     *
     * testSubject.setUnitOfWorkFactory(mockUnitOfWorkFactory);
     * testSubject.subscribe(String.class.getName(), new CommandHandler<String>() {
     * @Override
     * public Object handle(CommandMessage<String> command, UnitOfWork unitOfWork) throws Throwable {
     * throw new Exception();
     * }
     * });
     * testSubject.setRollbackConfiguration(new RollbackOnUncheckedExceptionConfiguration());
     *
     * testSubject.dispatch(GenericCommandMessage.asCommandMessage("Say hi!"), new CommandCallback<Object>() {
     * @Override
     * public void onSuccess(Object result) {
     * fail("Expected exception");
     * }
     *
     * @Override
     * public void onFailure(Throwable cause) {
     * assertThat(cause, is(Exception.class));
     * }
     * });
     *
     * verify(mockUnitOfWork).commit();
     * }*/


    public function testInterceptorChain_CommandHandledSuccessfully()
    {
        $mockInterceptor1 = \Phake::mock(CommandHandlerInterceptorInterface::class);
        $mockInterceptor2 = \Phake::mock(CommandHandlerInterceptorInterface::class);

        $commandHandler = \Phake::mock(CommandHandlerInterface::class);

        \Phake::when($mockInterceptor1)->handle(\Phake::anyParameters())->thenGetReturnByLambda(
            function (
                CommandMessageInterface $commandMessage,
                UnitOfWorkInterface $unitOfWork,
                InterceptorChainInterface $interceptorChain
            ) use ($mockInterceptor2) {
                return $mockInterceptor2->handle($commandMessage, $unitOfWork, $interceptorChain);
            }
        );

        \Phake::when($mockInterceptor2)->handle(\Phake::anyParameters())->thenGetReturnByLambda(
            function (
                CommandMessageInterface $commandMessage,
                UnitOfWorkInterface $unitOfWork,
                InterceptorChainInterface $interceptorChain
            ) use ($commandHandler) {
                return $commandHandler->handle($commandMessage, $unitOfWork);
            }
        );

        \Phake::when($commandHandler)->handle(\Phake::anyParameters())->thenReturn(new TestCommand("Hi there!"));

        $subject = new SimpleCommandBus(new DefaultUnitOfWorkFactory());
        $subject->setHandlerInterceptors([$mockInterceptor1, $mockInterceptor2]);
        $subject->subscribe(TestCommand::class, $commandHandler);

        $command = GenericCommandMessage::asCommandMessage(new TestCommand("Hi there!"));

        $callback = new ClosureCommandCallback(
            function ($result) {
                $this->assertEquals("Hi there!", $result->getText());
            },
            function ($exception) {
                $this->fail("Did not expect exception");
            }
        );

        $subject->dispatch($command, $callback);

        \Phake::inOrder(
            \Phake::verify($mockInterceptor1)->handle(\Phake::anyParameters()),
            \Phake::verify($mockInterceptor2)->handle(\Phake::anyParameters()),
            \Phake::verify($commandHandler)->handle(\Phake::anyParameters())
        );
    }

    /*
    @SuppressWarnings({"unchecked", "ThrowableInstanceNeverThrown"})
    @Test
    public void testInterceptorChain_CommandHandlerThrowsException() throws Throwable {
    CommandHandlerInterceptor mockInterceptor1 = mock(CommandHandlerInterceptor.class);
    final CommandHandlerInterceptor mockInterceptor2 = mock(CommandHandlerInterceptor.class);
    final CommandHandler<String> commandHandler = mock(CommandHandler.class);
    when(mockInterceptor1.handle(isA(CommandMessage.class), isA(UnitOfWork.class), isA(InterceptorChain.class)))
    .thenAnswer(new Answer<Object>() {
    @Override
    public Object answer(InvocationOnMock invocation) throws Throwable {
    return mockInterceptor2.handle((CommandMessage) invocation.getArguments()[0],
    (UnitOfWork) invocation.getArguments()[1],
    (InterceptorChain) invocation.getArguments()[2]);
    }
    });
    when(mockInterceptor2.handle(isA(CommandMessage.class), isA(UnitOfWork.class), isA(InterceptorChain.class)))
    .thenAnswer(new Answer<Object>() {
    @SuppressWarnings({"unchecked"})
    @Override
    public Object answer(InvocationOnMock invocation) throws Throwable {
    return commandHandler.handle((CommandMessage) invocation.getArguments()[0],
    (UnitOfWork) invocation.getArguments()[1]);
    }
    });

    testSubject.setHandlerInterceptors(Arrays.asList(mockInterceptor1, mockInterceptor2));
    when(commandHandler.handle(isA(CommandMessage.class), isA(UnitOfWork.class)))
    .thenThrow(new RuntimeException("Faking failed command handling"));
    testSubject.subscribe(String.class.getName(), commandHandler);

    testSubject.dispatch(GenericCommandMessage.asCommandMessage("Hi there!"), new CommandCallback<Object>() {
    @Override
    public void onSuccess(Object result) {
    fail("Expected exception to be thrown");
    }

    @Override
    public void onFailure(Throwable cause) {
    assertEquals("Faking failed command handling", cause.getMessage());
    }
    });

    InOrder inOrder = inOrder(mockInterceptor1, mockInterceptor2, commandHandler);
    inOrder.verify(mockInterceptor1).handle(isA(CommandMessage.class),
    isA(UnitOfWork.class), isA(InterceptorChain.class));
    inOrder.verify(mockInterceptor2).handle(isA(CommandMessage.class),
    isA(UnitOfWork.class), isA(InterceptorChain.class));
    inOrder.verify(commandHandler).handle(isA(GenericCommandMessage.class), isA(UnitOfWork.class));
    }

    @SuppressWarnings({"ThrowableInstanceNeverThrown", "unchecked"})
    @Test
    public void testInterceptorChain_InterceptorThrowsException() throws Throwable {
    CommandHandlerInterceptor mockInterceptor1 = mock(CommandHandlerInterceptor.class);
    final CommandHandlerInterceptor mockInterceptor2 = mock(CommandHandlerInterceptor.class);
    when(mockInterceptor1.handle(isA(CommandMessage.class), isA(UnitOfWork.class), isA(InterceptorChain.class)))
    .thenAnswer(new Answer<Object>() {
    @Override
    public Object answer(InvocationOnMock invocation) throws Throwable {
    return mockInterceptor2.handle((CommandMessage) invocation.getArguments()[0],
    (UnitOfWork) invocation.getArguments()[1],
    (InterceptorChain) invocation.getArguments()[2]);
    }
    });
    testSubject.setHandlerInterceptors(Arrays.asList(mockInterceptor1, mockInterceptor2));
    CommandHandler<String> commandHandler = mock(CommandHandler.class);
    when(commandHandler.handle(isA(CommandMessage.class), isA(UnitOfWork.class))).thenReturn("Hi there!");
    testSubject.subscribe(String.class.getName(), commandHandler);
    RuntimeException someException = new RuntimeException("Mocking");
    doThrow(someException).when(mockInterceptor2).handle(isA(CommandMessage.class),
    isA(UnitOfWork.class),
    isA(InterceptorChain.class));
    testSubject.dispatch(GenericCommandMessage.asCommandMessage("Hi there!"), new CommandCallback<Object>() {
    @Override
    public void onSuccess(Object result) {
    fail("Expected exception to be propagated");
    }

    @Override
    public void onFailure(Throwable cause) {
    assertEquals("Mocking", cause.getMessage());
    }
    });
    InOrder inOrder = inOrder(mockInterceptor1, mockInterceptor2, commandHandler);
    inOrder.verify(mockInterceptor1).handle(isA(CommandMessage.class),
    isA(UnitOfWork.class), isA(InterceptorChain.class));
    inOrder.verify(mockInterceptor2).handle(isA(CommandMessage.class),
    isA(UnitOfWork.class), isA(InterceptorChain.class));
    inOrder.verify(commandHandler, never()).handle(isA(CommandMessage.class), isA(UnitOfWork.class));
    }
   */
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

class CallbackCommandHandler implements CommandHandlerInterface
{

    private $callback;

    function __construct(\Closure $callback)
    {
        $this->callback = $callback;
    }

    public function handle(
        CommandMessageInterface $commandMessage,
        UnitOfWorkInterface $unitOfWork
    ) {
        $cb = $this->callback;

        return $cb($commandMessage, $unitOfWork);
    }

}
