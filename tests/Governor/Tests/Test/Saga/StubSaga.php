<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 20/12/14
 * Time: 21:18
 */

namespace Governor\Tests\Test\Saga;

use Governor\Framework\Annotations as Governor;
use Governor\Framework\Domain\GenericEventMessage;
use Governor\Framework\EventHandling\EventBusInterface;
use Governor\Framework\Saga\Annotation\AbstractAnnotatedSaga;
use Governor\Framework\Saga\AssociationValue;
use JMS\Serializer\Annotation\Exclude;

class StubSaga extends AbstractAnnotatedSaga
{
    //private static final int TRIGGER_DURATION_MINUTES = 10;
    //private transient StubGateway stubGateway;

    /**
     * @Exclude
     * @var EventBusInterface
     */
    private $eventBus;
    //private transient EventScheduler scheduler;
    private $handledEvents = array();
    //private ScheduleToken timer;


    /**
     * @param TriggerSagaStartEvent $event
     * @Governor\StartSaga()
     * @Governor\SagaEventHandler(associationProperty="identifier")
     */
    public function onSagaStart(TriggerSagaStartEvent $event)
    {
        $this->handledEvents[] = $event;
        // !!! TODO think about a timer implementation for PHP
        //timer = scheduler.schedule(Duration.standardMinutes(TRIGGER_DURATION_MINUTES),
        //      new GenericEventMessage<TimerTriggeredEvent>(new TimerTriggeredEvent(event.getIdentifier())));
    }

    /**
     * @param ForceTriggerSagaStartEvent $event
     * @Governor\StartSaga(forceNew=true)
     * @Governor\SagaEventHandler(associationProperty="identifier")
     */
    public function onForceSagaStart(ForceTriggerSagaStartEvent $event)
    {
        $this->handledEvents[] = $event;
        // !!! TODO think about a timer implementation for PHP
        //timer = scheduler.schedule(Duration.standardMinutes(TRIGGER_DURATION_MINUTES),
        //      new GenericEventMessage<TimerTriggeredEvent>(new TimerTriggeredEvent(event.getIdentifier())));
    }

    /**
     * @param TriggerExistingSagaEvent $event
     * @Governor\SagaEventHandler(associationProperty="identifier")
     */
    public function onTriggerEvent(TriggerExistingSagaEvent $event)
    {
        $this->handledEvents[] = $event;
        $this->eventBus->publish(array(new GenericEventMessage(new SagaWasTriggeredEvent($this))));
    }

    /**
     * @param TriggerSagaEndEvent $event
     * @Governor\EndSaga()
     * @Governor\SagaEventHandler(associationProperty="identifier")
     */
    public function onSagaEnd(TriggerSagaEndEvent $event)
    {
        $this->handledEvents[] = $event;
    }

    /*

        @SagaEventHandler(associationProperty = "identifier")
        public void handleFalseEvent(TriggerExceptionWhileHandlingEvent event) {
        handledEvents.add(event);
        throw new RuntimeException("This is a mock exception");
    }

        @SagaEventHandler(associationProperty = "identifier")
        public void handleTriggerEvent(TimerTriggeredEvent event) {
        handledEvents.add(event);
        String result = stubGateway.send("Say hi!");
            if (result != null) {
                stubGateway.send(result);
            }
        }

        @SagaEventHandler(associationProperty = "identifier")
        public void handleResetTriggerEvent(ResetTriggerEvent event) {
        handledEvents.add(event);
        scheduler.cancelSchedule(timer);
        timer = scheduler.schedule(Duration.standardMinutes(TRIGGER_DURATION_MINUTES),
                new GenericEventMessage<TimerTriggeredEvent>(new TimerTriggeredEvent(event.getIdentifier())));
        }*/

    public function getEventBus()
    {
        return $this->eventBus;
    }

    /**
     * @param EventBusInterface $eventBus
     * @Governor\Inject(service="governor.event_bus.default")
     */
    public function setEventBus(EventBusInterface $eventBus)
    {
        $this->eventBus = $eventBus;
    }

    /*
        public EventScheduler getScheduler() {
            return scheduler;
        }

        public void setScheduler(EventScheduler scheduler) {
        this.scheduler = scheduler;
    }

        public void setStubGateway(StubGateway stubGateway) {
        this.stubGateway = stubGateway;
    }*/


    public function associateWith(AssociationValue $associationValue)
    {
        parent::associateWith($associationValue);
    }


    public function removeAssociationWith(AssociationValue $associationValue)
    {
        parent::removeAssociationWith($associationValue);
    }


    public function end()
    {
        parent::end();
    }
}