<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\CommandHandling;

use Governor\Framework\Domain\MetaData;
use Governor\Framework\CommandHandling\CommandMessageInterface;
use Governor\Framework\UnitOfWork\DefaultUnitOfWork;
use Governor\Framework\UnitOfWork\CurrentUnitOfWork;
use Governor\Framework\UnitOfWork\UnitOfWorkInterface;

/**
 * Description of SimpleCommandBus
 *
 * @author david
 */
class SimpleCommandBusTest extends \PHPUnit_Framework_TestCase
{

    private $commandBus;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->commandBus = new SimpleCommandBus();
        $this->commandBus->setLogger($this->getMock('Psr\Log\LoggerInterface'));
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
        
    }

    /**
     * @expectedException Governor\Framework\CommandHandling\NoHandlerForCommandException
     */
    public function testDispatchCommand_NoHandlerSubscribed()
    {
        $this->commandBus->dispatch(new GenericCommandMessage(new TestCommand('hi'),
                MetaData::emptyInstance()));
    }

    /**
     * @expectedException Governor\Framework\CommandHandling\NoHandlerForCommandException
     */
    public function testDispatchCommand_HandlerUnsubscribed()
    {
        $commandHandler = new TestCommandHandler();
        $this->commandBus->subscribe(get_class(new TestCommand('hi')),
                $commandHandler);
        $this->commandBus->unsubscribe(get_class(new TestCommand('hi')),
                $commandHandler);

        $this->commandBus->dispatch(new GenericCommandMessage(new TestCommand('hi'),
                MetaData::emptyInstance()));
    }

    public function testDispatchCommand_HandlerSubscribed()
    {
        $commandHandler = new TestCommandHandler();
        $this->commandBus->subscribe(TestCommand::class, $commandHandler);

        $command = new TestCommand('hi');

        $this->commandBus->dispatch(GenericCommandMessage::asCommandMessage($command),
                new CommandCallback(function ($result) use ($command) {
            $this->assertEquals($command, $result->getPayload());
        },
                function ($exception) {
            $this->fail('Exception not expected');
        }));
    }

    public function testDispatchCommand_ImplicitUnitOfWorkIsCommittedOnReturnValue()
    {
        //$spyUnitOfWorkFactory = spy(new DefaultUnitOfWorkFactory());
        $command = new TestCommand("Say hi!");
        $test = $this;

        $this->commandBus->subscribe(TestCommand::class,
                new CallbackCommandHandler(function (CommandMessageInterface $commandMessage,
                UnitOfWorkInterface $unitOfWork) use ($test) {
            $test->assertTrue(CurrentUnitOfWork::isStarted());
            $test->assertTrue($unitOfWork->isStarted());
            $test->assertNotNull(CurrentUnitOfWork::get());
            $test->assertNotNull($unitOfWork);
            $test->assertSame(CurrentUnitOfWork::get(), $unitOfWork);
            return $commandMessage;
        }));

        $callback = new CommandCallback(function($result) use ($command) {
            $this->assertEquals($command, $result->getPayload());
        },
                function ($exception) {
            $this->fail("Did not expect exception");
        });



        $this->commandBus->dispatch(GenericCommandMessage::asCommandMessage($command),
                $callback);
        //verify(spyUnitOfWorkFactory).createUnitOfWork();
        $this->assertFalse(CurrentUnitOfWork::isStarted());
    }

    public function testDispatchCommand_ImplicitUnitOfWorkIsRolledBackOnException()
    {
        $command = new TestCommand("Say hi!");
        $test = $this;

        $this->commandBus->subscribe(TestCommand::class,
                new CallbackCommandHandler(function (CommandMessageInterface $commandMessage,
                UnitOfWorkInterface $unitOfWork) use ($test) {
            $test->assertTrue(CurrentUnitOfWork::isStarted());
            $test->assertNotNull(CurrentUnitOfWork::get());

            throw new \RuntimeException("exception");
        }));

        $callback = new CommandCallback(function($result) use ($command) {
            $this->fail("Did not expect exception");
        },
                function ($exception) {
            $this->assertEquals(\RuntimeException::class, get_class($exception));
        });

        $this->commandBus->dispatch(GenericCommandMessage::asCommandMessage($command),
                $callback);

        $this->assertFalse(CurrentUnitOfWork::isStarted());
    }

    public function testUnsubscribe_HandlerNotKnown()
    {
        $this->commandBus->unsubscribe(TestCommand::class,
                new TestCommandHandler());
    }

    /**
      @Test
      public void testDispatchCommand_UnitOfWorkIsCommittedOnCheckedException() {
      UnitOfWorkFactory mockUnitOfWorkFactory = mock(DefaultUnitOfWorkFactory.class);
      UnitOfWork mockUnitOfWork = mock(UnitOfWork.class);
      when(mockUnitOfWorkFactory.createUnitOfWork()).thenReturn(mockUnitOfWork);

      testSubject.setUnitOfWorkFactory(mockUnitOfWorkFactory);
      testSubject.subscribe(String.class.getName(), new CommandHandler<String>() {
      @Override
      public Object handle(CommandMessage<String> command, UnitOfWork unitOfWork) throws Throwable {
      throw new Exception();
      }
      });
      testSubject.setRollbackConfiguration(new RollbackOnUncheckedExceptionConfiguration());

      testSubject.dispatch(GenericCommandMessage.asCommandMessage("Say hi!"), new CommandCallback<Object>() {
      @Override
      public void onSuccess(Object result) {
      fail("Expected exception");
      }

      @Override
      public void onFailure(Throwable cause) {
      assertThat(cause, is(Exception.class));
      }
      });

      verify(mockUnitOfWork).commit();
      }

      @SuppressWarnings({"unchecked"})
      @Test
      public void testInterceptorChain_CommandHandledSuccessfully() throws Throwable {
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
      @Override
      public Object answer(InvocationOnMock invocation) throws Throwable {
      return commandHandler.handle((CommandMessage) invocation.getArguments()[0],
      (UnitOfWork) invocation.getArguments()[1]);
      }
      });
      testSubject.setHandlerInterceptors(Arrays.asList(mockInterceptor1, mockInterceptor2));
      when(commandHandler.handle(isA(CommandMessage.class), isA(UnitOfWork.class))).thenReturn("Hi there!");
      testSubject.subscribe(String.class.getName(), commandHandler);

      testSubject.dispatch(GenericCommandMessage.asCommandMessage("Hi there!"), new CommandCallback<Object>() {
      @Override
      public void onSuccess(Object result) {
      assertEquals("Hi there!", result);
      }

      @Override
      public void onFailure(Throwable cause) {
      throw new RuntimeException("Unexpected exception", cause);
      }
      });

      InOrder inOrder = inOrder(mockInterceptor1, mockInterceptor2, commandHandler);
      inOrder.verify(mockInterceptor1).handle(isA(CommandMessage.class),
      isA(UnitOfWork.class), isA(InterceptorChain.class));
      inOrder.verify(mockInterceptor2).handle(isA(CommandMessage.class),
      isA(UnitOfWork.class), isA(InterceptorChain.class));
      inOrder.verify(commandHandler).handle(isA(GenericCommandMessage.class), isA(UnitOfWork.class));
      }

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

class TestCommand
{

    private $text;

    function __construct($text)
    {
        $this->text = $text;
    }

    public function getText()
    {
        return $this->text;
    }

}

class TestCommandHandler implements CommandHandlerInterface
{

    public function handle(CommandMessageInterface $commandMessage,
            UnitOfWorkInterface $unitOfWork)
    {
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

    public function handle(CommandMessageInterface $commandMessage,
            UnitOfWorkInterface $unitOfWork)
    {
        $cb = $this->callback;
        return $cb($commandMessage, $unitOfWork);
    }

}
