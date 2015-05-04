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

namespace Governor\Tests\EventHandling\Replay;

use Governor\Framework\Common\Annotation\SimpleAnnotationReaderFactory;
use Governor\Framework\Annotations\EventHandler;
use Governor\Framework\Domain\GenericDomainEventMessage;
use Governor\Framework\Domain\GenericEventMessage;
use Governor\Framework\EventHandling\InMemoryEventListenerRegistry;
use Governor\Framework\EventHandling\Listeners\AnnotatedEventListenerAdapter;
use Governor\Framework\EventHandling\EventListenerInterface;
use Governor\Framework\EventHandling\SimpleEventBus;
use Governor\Framework\EventStore\Management\EventStoreManagementInterface;
use Governor\Framework\EventStore\Orm\Criteria\OrmCriteriaBuilder;
use Governor\Framework\EventHandling\Replay\ReplayAwareInterface;
use Governor\Framework\EventHandling\Replay\IncomingMessageHandlerInterface;
use Governor\Framework\EventHandling\Replay\ReplayingEventBus;

/**
 * Description of ReplayingEventBusTest
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
class ReplayingEventBusTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var ReplayingEventBus
     */
    private $testSubject;

    /**
     * @var IncomingMessageHandlerInterface
     */
    private $mockMessageHandler;

    /**
     * @var EventStoreManagementInterface
     */
    private $mockEventStore;

    /**
     * @var SimpleEventBus
     */
    private $delegate;

    /**
     * @var array
     */
    private $messages = array();

    public function setUp()
    {
        $this->mockMessageHandler = \Phake::mock(IncomingMessageHandlerInterface::class);
        $this->mockEventStore = \Phake::mock(EventStoreManagementInterface::class);
        $this->delegate = \Phake::mock(SimpleEventBus::class);

        \Phake::when($this->delegate)->getEventListenerRegistry()->thenReturn(new InMemoryEventListenerRegistry());

        $this->testSubject = new ReplayingEventBus(
            $this->delegate,
            $this->mockEventStore, $this->mockMessageHandler
        );

        //$this->testSubject->setLogger($this->getMock(\Psr\Log\LoggerInterface::class));

        for ($i = 0; $i < 10; $i++) {
            $this->messages[] = new GenericDomainEventMessage(
                "id", $i,
                new Payload("payload text")
            );
        }
    }

    public function testAnnotatedHandlersRecognized()
    {
//        $delegate = new SimpleEventBus(new InMemoryEventListenerRegistry());

        $listener = new MyReplayAwareListener();
        $adapter = new AnnotatedEventListenerAdapter($listener, $this->delegate, new SimpleAnnotationReaderFactory());

        $this->delegate->getEventListenerRegistry()->subscribe($adapter);

        $this->testSubject->startReplay();

        $this->assertEquals(0, $listener->counter);
        $this->assertEquals(1, $listener->before);
        $this->assertEquals(1, $listener->after);
    }

    public function testRegularMethodsDelegated()
    {
        $this->testSubject->getEventListenerRegistry();

        \Phake::verify($this->delegate)->getEventListenerRegistry();
    }

    public function testReplay()
    {
        $messages = $this->messages;

        \Phake::when($this->mockEventStore)->visitEvents(\Phake::anyParameters())
            ->thenGetReturnByLambda(
                function ($visitor, $criteria) use ($messages) {
                    foreach ($messages as $message) {
                        $visitor->doWithEvent($message);
                    }
                }
            );

        $this->testSubject->startReplay();

        \Phake::inOrder(
            \Phake::verify($this->mockMessageHandler)->prepareForReplay(\Phake::anyParameters()),
            \Phake::verify($this->mockEventStore)->visitEvents(\Phake::anyParameters()),
            \Phake::verify($this->delegate, \Phake::times(10))->publish(\Phake::anyParameters()),
            \Phake::verify($this->mockMessageHandler)->processBacklog(\Phake::anyParameters())
        );

        /* !!! TODO
          inOrder.verify(mockMessageHandler).releaseMessage(eq(delegateCluster), isA(DomainEventMessage.class));
         */
    }

    public function testReplay_HandlersSubscribedTwice()
    {
        $replayAwareListener = \Phake::mock(ReplayAwareListenerInterface::class);

        $this->testSubject->getEventListenerRegistry()->subscribe($replayAwareListener);
        $this->testSubject->getEventListenerRegistry()->subscribe($replayAwareListener);

        $this->testSubject->startReplay();

        \Phake::verify($replayAwareListener, \Phake::times(1))->beforeReplay();
        \Phake::verify($replayAwareListener, \Phake::times(1))->afterReplay();
    }

    public function testPartialReplay_withCriteria()
    {
        $messages = $this->messages;

        \Phake::when($this->mockEventStore)->visitEvents(\Phake::anyParameters())
            ->thenGetReturnByLambda(
                function ($visitor, $criteria) use ($messages) {
                    foreach ($messages as $message) {
                        $visitor->doWithEvent($message);
                    }
                }
            );

        \Phake::when($this->mockEventStore)->newCriteriaBuilder()->thenReturn(new OrmCriteriaBuilder());

        $criteria = $this->testSubject->newCriteriaBuilder()->property("abc")->isNot(false);
        $this->testSubject->startReplay($criteria);

        \Phake::inOrder(
            \Phake::verify($this->mockMessageHandler)->prepareForReplay(\Phake::anyParameters()),
            \Phake::verify($this->mockEventStore)->visitEvents(\Phake::anyParameters()),
            \Phake::verify($this->delegate, \Phake::times(10))->publish(\Phake::anyParameters()),
            //                \Phake::verify($this->mockMessageHandler, \Phake::times(10))->releaseMessage(\Phake::anyParameters()),
            \Phake::verify($this->mockMessageHandler)->processBacklog(\Phake::anyParameters())
        );

        /*
          inOrder.verify(mockEventStore).visitEvents(refEq(criteria), isA(EventVisitor.class));
          for (int i = 0; i < 10; i++) {
          inOrder.verify(delegateCluster).publish(isA(DomainEventMessage.class));
          inOrder.verify(mockMessageHandler).releaseMessage(eq(delegateCluster), isA(DomainEventMessage.class));
          }
          inOrder.verify(mockMessageHandler).processBacklog(delegateCluster); */
    }

    public function testEventReceivedDuringReplay()
    {
        $concurrentMessage = new GenericEventMessage(new Payload("Concurrent MSG"));
        $self = $this;

        \Phake::when($this->mockEventStore)->visitEvents(\Phake::anyParameters())
            ->thenGetReturnByLambda(
                function ($visitor, $criteria) use ($concurrentMessage, $self) {
                    $self->assertTrue($self->testSubject->isInReplayMode());
                    $self->testSubject->publish(array($concurrentMessage));

                    foreach ($self->messages as $message) {
                        $visitor->doWithEvent($message);
                    }
                }
            );

        $listener = \Phake::mock(ReplayAwareListenerInterface::class);
        $this->testSubject->getEventListenerRegistry()->subscribe($listener);
        $this->testSubject->startReplay();

        \Phake::inOrder(
            \Phake::verify($this->mockMessageHandler)->prepareForReplay(\Phake::anyParameters()),
            \Phake::verify($listener)->beforeReplay(),
            \Phake::verify($this->mockEventStore)->visitEvents(\Phake::anyParameters()),
            \Phake::verify($this->mockMessageHandler)->onIncomingMessages(\Phake::anyParameters()),
            \Phake::verify($this->delegate, \Phake::times(10))->publish(\Phake::anyParameters()),
            \Phake::verify($listener)->afterReplay(),
            \Phake::verify($this->mockMessageHandler)->processBacklog(\Phake::anyParameters())
        );

        \Phake::verify($this->delegate, \Phake::never())->publish($concurrentMessage);
     //   \Phake::verify($this->delegate)->subscribe($listener);
    }

    /*
      @Test
      public void testIntermediateTransactionsCommitted() {
      testSubject = new ReplayingEventBus(delegateCluster, mockEventStore, mockTransactionManager, 5,
      mockMessageHandler);
      doAnswer(new Answer() {
      @Override
      public Object answer(InvocationOnMock invocation) throws Throwable {
      EventVisitor visitor = (EventVisitor) invocation.getArguments()[0];
      for (DomainEventMessage message : messages) {
      visitor.doWithEvent(message);
      }
      return null;
      }
      }).when(mockEventStore).visitEvents(isA(EventVisitor.class));

      testSubject.startReplay();

      InOrder inOrder = inOrder(mockEventStore, mockTransactionManager, delegateCluster, mockMessageHandler);

      inOrder.verify(mockMessageHandler).prepareForReplay(isA(Cluster.class));
      inOrder.verify(mockTransactionManager).startTransaction();
      inOrder.verify(mockEventStore).visitEvents(isA(EventVisitor.class));
      for (int i = 0; i < 5; i++) {
      inOrder.verify(delegateCluster).publish(isA(DomainEventMessage.class));
      inOrder.verify(mockMessageHandler).releaseMessage(eq(delegateCluster), isA(DomainEventMessage.class));
      }
      inOrder.verify(mockTransactionManager).commitTransaction(anyObject());
      inOrder.verify(mockTransactionManager).startTransaction();

      for (int i = 5; i < 10; i++) {
      inOrder.verify(delegateCluster).publish(isA(DomainEventMessage.class));
      inOrder.verify(mockMessageHandler).releaseMessage(eq(delegateCluster), isA(DomainEventMessage.class));
      }
      inOrder.verify(mockMessageHandler).processBacklog(delegateCluster);
      inOrder.verify(mockTransactionManager).commitTransaction(anyObject());
      inOrder.verify(mockTransactionManager, never()).startTransaction();
      }

     */
}

interface ReplayAwareListenerInterface extends ReplayAwareInterface, EventListenerInterface
{

}

class MyReplayAwareListener implements ReplayAwareInterface
{

    public $counter;
    public $before;
    public $after;
    public $failed;

    /**
     * @EventHandler
     */
    public function handleAll(Payload $payload)
    {
        $this->counter++;
    }

    public function beforeReplay()
    {
        $this->before++;
    }

    public function afterReplay()
    {
        $this->after++;
    }

    public function onReplayFailed(\Exception $cause = null)
    {
        $this->failed++;
    }

}

class Payload
{

    public $text;

    function __construct($text)
    {
        $this->text = $text;
    }

}
