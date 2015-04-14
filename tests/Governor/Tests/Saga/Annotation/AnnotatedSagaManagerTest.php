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

namespace Governor\Tests\Saga\Annotation;

use Governor\Framework\Annotations\EndSaga;
use Governor\Framework\Annotations\SagaEventHandler;
use Governor\Framework\Annotations\StartSaga;
use Governor\Framework\Correlation\CorrelationDataHolder;
use Governor\Framework\Correlation\CorrelationDataProviderInterface;
use Governor\Framework\Domain\EventMessageInterface;
use Governor\Framework\Domain\GenericEventMessage;
use Governor\Framework\Domain\MetaData;
use Governor\Framework\Saga\Annotation\AbstractAnnotatedSaga;
use Governor\Framework\Saga\Annotation\AnnotatedSagaManager;
use Governor\Framework\Saga\AssociationValue;
use Governor\Framework\Saga\GenericSagaFactory;
use Governor\Framework\Saga\Repository\Memory\InMemorySagaRepository;

/**
 * AnnotatedSagaManager unit tests.
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
class AnnotatedSagaManagerTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var InMemorySagaRepository|\Phake_IMock
     */
    private $sagaRepository;

    /**
     * @var AnnotatedSagaManager
     */
    private $manager;

    public function setUp()
    {
        CorrelationDataHolder::clear();
        $this->sagaRepository = \Phake::partialMock(InMemorySagaRepository::class);

        $this->manager = new AnnotatedSagaManager(
            $this->sagaRepository,
            new GenericSagaFactory(), array(MyTestSaga::class)
        );
    }

    public function tearDown()
    {
        CorrelationDataHolder::clear();
    }

    private function repositoryContents($lookupValue, $sagaType)
    {
        $identifiers = $this->sagaRepository->find(
            $sagaType,
            new AssociationValue("myIdentifier", $lookupValue)
        );
        $sagas = array();
        foreach ($identifiers as $identifier) {
            $sagas[] = $this->sagaRepository->load($identifier);
        }

        return $sagas;
    }

    public function testCreationPolicy_NoneExists()
    {
        $this->manager->handle(new GenericEventMessage(new StartingEvent("123")));
        $this->assertCount(
            1,
            $this->repositoryContents("123", MyTestSaga::class)
        );
    }

    public function testCreationPolicy_OneAlreadyExists()
    {
        $this->manager->handle(new GenericEventMessage(new StartingEvent("123")));
        $this->manager->handle(new GenericEventMessage(new StartingEvent("123")));
        $this->assertCount(
            1,
            $this->repositoryContents("123", MyTestSaga::class)
        );
    }

    public function testHandleUnrelatedEvent()
    {
        $this->manager->handle(new GenericEventMessage(new \stdClass()));
        \Phake::verify($this->sagaRepository, \Phake::never())->find($this->any());
    }

    public function testCreationPolicy_CreationForced()
    {
        $startingEvent = new StartingEvent("123");

        $this->manager->handle(new GenericEventMessage($startingEvent));
        $this->manager->handle(new GenericEventMessage(new ForcingStartEvent("123")));


        $sagas = $this->repositoryContents("123", MyTestSaga::class);
        $this->assertCount(2, $sagas);

        foreach ($sagas as $saga) {
            if (in_array($startingEvent, $saga->getCapturedEvents())) {
                $this->assertCount(2, $saga->getCapturedEvents());
            }
            $this->assertTrue(count($saga->getCapturedEvents()) >= 1);
        }
    }

    public function testCreationPolicy_SagaNotCreated()
    {
        $this->manager->handle(new GenericEventMessage(new MiddleEvent("123")));
        $this->assertCount(
            0,
            $this->repositoryContents("123", MyTestSaga::class)
        );
    }

    // !!! TODO find a suitable PHP implementation
    public function testMostSpecificHandlerEvaluatedFirst()
    {
        $this->manager->handle(new GenericEventMessage(new StartingEvent("12")));
        $this->manager->handle(new GenericEventMessage(new StartingEvent("23")));

        $this->assertCount(1, $this->repositoryContents("12", MyTestSaga::class));
        $this->assertCount(1, $this->repositoryContents("23", MyTestSaga::class));

        $this->manager->handle(new GenericEventMessage(new MiddleEvent("12")));
        $this->manager->handle(
            new GenericEventMessage(
                new MiddleEvent("23"),
                new MetaData(array("catA" => "value"))
            )
        );

        $this->assertEquals(
            0,
            $this->repositoryContents("12", MyTestSaga::class)[0]->getSpecificHandlerInvocations()
        );
//        $this->assertEquals(1,
        //              $this->repositoryContents("23", MyTestSaga::class)[0]->getSpecificHandlerInvocations());
        //$this->assertEquals(0, repositoryContents("12", MyTestSaga.class).iterator().next().getSpecificHandlerInvocations());
        //$this->assertEquals(1, repositoryContents("23", MyTestSaga.class).iterator().next().getSpecificHandlerInvocations());
    }

    public function testLifecycle_DestroyedOnEnd()
    {
        $this->manager->handle(new GenericEventMessage(new StartingEvent("12")));
        $this->manager->handle(new GenericEventMessage(new StartingEvent("23")));
        $this->manager->handle(new GenericEventMessage(new MiddleEvent("12")));
        //manager.handle(new GenericEventMessage<MiddleEvent>(new MiddleEvent("23"), Collections.singletonMap("catA",
        //"value")));

        $this->assertCount(1, $this->repositoryContents("12", MyTestSaga::class));
        $this->assertCount(1, $this->repositoryContents("23", MyTestSaga::class));

        $this->assertEquals(
            0,
            $this->repositoryContents("12", MyTestSaga::class)[0]->getSpecificHandlerInvocations()
        );
        //$this->assertEquals(1, repositoryContents("23", MyTestSaga.class).iterator().next().getSpecificHandlerInvocations());

        $this->manager->handle(new GenericEventMessage(new EndingEvent("12")));

        $this->assertCount(1, $this->repositoryContents("23", MyTestSaga::class));
        $this->assertCount(0, $this->repositoryContents("12", MyTestSaga::class));

        $this->manager->handle(new GenericEventMessage(new EndingEvent("23")));
        $this->assertCount(0, $this->repositoryContents("23", MyTestSaga::class));
        $this->assertCount(0, $this->repositoryContents("12", MyTestSaga::class));
    }

    public function testCorrelationDataReadFromProvider()
    {
        $correlationDataProvider = \Phake::mock(CorrelationDataProviderInterface::class);
        \Phake::when($correlationDataProvider)->correlationDataFor(\Phake::anyParameters())->thenReturn(array());

        $this->manager->setCorrelationDataProvider($correlationDataProvider);

        $this->manager->handle(
            new GenericEventMessage(
                new StartingEvent(
                    "12", new MetaData(
                        array(
                            'key' => 'val'
                        )
                    )
                )
            )
        );

        \Phake::verify($correlationDataProvider)->correlationDataFor(
            \Phake::capture($message)
        );

        $this->assertInstanceOf(EventMessageInterface::class, $message);
    }


    public function testCorrelationDataReadFromProviders()
    {
        $correlationDataProvider1 = \Phake::mock(CorrelationDataProviderInterface::class);
        $correlationDataProvider2 = \Phake::mock(CorrelationDataProviderInterface::class);

        $this->manager->setCorrelationDataProviders(array($correlationDataProvider1, $correlationDataProvider2));

        $this->manager->handle(
            new GenericEventMessage(
                new StartingEvent(
                    "12", new MetaData(
                        array(
                            'key' => 'val'
                        )
                    )
                )
            )
        );

        \Phake::verify($correlationDataProvider1)->correlationDataFor(\Phake::anyParameters());
        \Phake::verify($correlationDataProvider2)->correlationDataFor(\Phake::anyParameters());
    }


    /*
      @Test
      public void testNullAssociationValueDoesNotThrowNullPointer() {
      manager.handle(asEventMessage(new StartingEvent(null)));
      }

      @Test
      public void testLifeCycle_ExistingInstanceIgnoresEvent() {
      manager.handle(new GenericEventMessage<StartingEvent>(new StartingEvent("12")));
      manager.handle(new GenericEventMessage<StubDomainEvent>(new StubDomainEvent()));
      assertEquals(1, repositoryContents("12", MyTestSaga.class).size());
      assertEquals(1, repositoryContents("12", MyTestSaga.class).iterator().next().getCapturedEvents().size());
      } */

    public function testLifeCycle_IgnoredEventDoesNotCreateInstance()
    {
        $this->manager->handle(new GenericEventMessage(new EndingEvent("xx")));
        $this->assertCount(0, $this->repositoryContents("12", MyTestSaga::class));
    }

    /*
      @Test
      public void testSagaTypeTakenIntoConsiderationWhenCheckingForSagasIncreation() throws InterruptedException {
      manager = new AnnotatedSagaManager(sagaRepository, new SimpleEventBus(),
      MyOtherTestSaga.class, MyTestSaga.class);

      ExecutorService executorService = Executors.newFixedThreadPool(8);
      for (int i = 0; i < 100; i++) {
      executorService.execute(new HandleEventTask(
      GenericEventMessage.asEventMessage(new StartingEvent("id" + i))));
      executorService.execute(new HandleEventTask(
      GenericEventMessage.asEventMessage(new OtherStartingEvent("id" + i))));
      }
      executorService.shutdown();
      executorService.awaitTermination(10, TimeUnit.SECONDS);

      for (int i = 0; i < 100; i++) {
      assertEquals("MyTestSaga missing for id" + i, 1, repositoryContents("id" + i, MyTestSaga.class).size());
      assertEquals("MyOtherTestSaga missing for id" + i, 1, repositoryContents("id" + i, MyOtherTestSaga.class).size());
      }
      }

      @Test(timeout = 5000)
      public void testEventForSagaIsHandledWhenSagaIsBeingCreated() throws InterruptedException {
      ExecutorService executor = Executors.newSingleThreadExecutor();
      final CountDownLatch awaitStart = new CountDownLatch(1);
      executor.execute(new Runnable() {
      @Override
      public void run() {
      manager.handle(new GenericEventMessage<StartingEvent>(new SlowStartingEvent("12", awaitStart, 100)));
      }
      });
      awaitStart.await();
      manager.handle(asEventMessage(new MiddleEvent("12")));
      executor.shutdown();
      executor.awaitTermination(1, TimeUnit.SECONDS);

      assertEquals(1, repositoryContents("12", MyTestSaga.class).size());
      assertEquals(2, repositoryContents("12", MyTestSaga.class).iterator().next().getCapturedEvents().size());
      }



      public static class MyOtherTestSaga extends AbstractAnnotatedSaga {

      @StartSaga
      @SagaEventHandler(associationProperty = "myIdentifier")
      public void handleSomeEvent(OtherStartingEvent event) throws InterruptedException {
      }
      }

      public static class OtherStartingEvent extends MyIdentifierEvent {

      private final CountDownLatch countDownLatch;

      protected OtherStartingEvent(String myIdentifier) {
      this(myIdentifier, null);
      }

      public OtherStartingEvent(String id, CountDownLatch countDownLatch) {
      super(id);
      this.countDownLatch = countDownLatch;
      }
      }

      public static class SlowStartingEvent extends StartingEvent {


      private final CountDownLatch startCdl;
      private final long duration;

      protected SlowStartingEvent(String myIdentifier, CountDownLatch startCdl, long duration) {
      super(myIdentifier);
      this.startCdl = startCdl;
      this.duration = duration;
      }

      public long getDuration() {
      return duration;
      }

      public CountDownLatch getStartCdl() {
      return startCdl;
      }
      }

      private class HandleEventTask implements Runnable {

      private final EventMessage<?> eventMessage;

      public HandleEventTask(EventMessage<?> eventMessage) {
      this.eventMessage = eventMessage;
      }

      @Override
      public void run() {
      manager.handle(eventMessage);
      }
      } */
}

abstract class MyIdentifierEvent
{

    private $myIdentifier;

    public function __construct($myIdentifier)
    {
        $this->myIdentifier = $myIdentifier;
    }

    public function getMyIdentifier()
    {
        return $this->myIdentifier;
    }

}

class StartingEvent extends MyIdentifierEvent
{

    public function __construct($myIdentifier)
    {
        parent::__construct($myIdentifier);
    }

}

class ForcingStartEvent extends MyIdentifierEvent
{

    public function __construct($myIdentifier)
    {
        parent::__construct($myIdentifier);
    }

}

class EndingEvent extends MyIdentifierEvent
{

    public function __construct($myIdentifier)
    {
        parent::__construct($myIdentifier);
    }

}

class MiddleEvent extends MyIdentifierEvent
{

    public function __construct($myIdentifier)
    {
        parent::__construct($myIdentifier);
    }

}

class MyTestSaga extends AbstractAnnotatedSaga
{

    private $capturedEvents = array();
    private $specificHandlerInvocations = 0;

    /**
     * @param StartingEvent $event
     *
     * @StartSaga
     * @SagaEventHandler(associationProperty = "myIdentifier")
     */
    public function handleStartingEvent(StartingEvent $event)
    {
        $this->capturedEvents[] = $event;
    }


    /**
     * @param ForcingStartEvent $event
     *
     * @StartSaga(forceNew = true)
     * @SagaEventHandler(associationProperty = "myIdentifier")
     */
    public function handleForcingStartEvent(ForcingStartEvent $event)
    {
        $this->capturedEvents[] = $event;
    }

    /**
     * @param EndingEvent $event
     *
     * @EndSaga
     * @SagaEventHandler(associationProperty = "myIdentifier")
     */
    public function handleEndingEvent(EndingEvent $event)
    {
        $this->capturedEvents[] = $event;
    }

    /**
     * @param MiddleEvent $event
     *
     * @SagaEventHandler(associationProperty = "myIdentifier")
     */
    public function handleMiddleEvent(MiddleEvent $event)
    {
        $this->capturedEvents[] = $event;
    }

    /**
     * @SagaEventHandler(associationProperty = "myIdentifier")
     */
    //@MetaData(value = "catA", required = true) String category
    public function handleSpecificMiddleEvent(MiddleEvent $event)
    {
        // this handler is more specific, but requires meta data that not all events might have
        $this->capturedEvents[] = $event;
        $this->specificHandlerInvocations++;
    }

    public function getCapturedEvents()
    {
        return $this->capturedEvents;
    }

    public function getSpecificHandlerInvocations()
    {
        return $this->specificHandlerInvocations;
    }

}
