<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 19/12/14
 * Time: 21:14
 */

namespace Governor\Framework\Test\Saga;

use Governor\Framework\Domain\EventMessageInterface;
use Governor\Framework\EventHandling\EventBusInterface;
use Governor\Framework\EventHandling\EventListenerInterface;
use Governor\Framework\Test\GovernorAssertionError;
use Governor\Framework\Test\Matchers\Matchers;
use Hamcrest\Matcher;
use Hamcrest\StringDescription;

class EventValidator implements EventListenerInterface
{
    private $publishedEvents = array();
    /**
     * @var EventBusInterface
     */
    private $eventBus;

    /**
     * Initializes the event validator to monitor the given <code>eventBus</code>.
     *
     * @param EventBusInterface $eventBus the event bus to monitor
     */
    public function __construct(EventBusInterface $eventBus = null)
    {
        $this->eventBus = $eventBus;
    }

    /**
     * Asserts that events have been published matching the given <code>matcher</code>.
     *
     * @param Matcher $matcher The matcher that will validate the actual events
     * @throws GovernorAssertionError
     */
    public function assertPublishedEventsMatching(Matcher $matcher)
    {
        if (!$matcher->matches($this->publishedEvents)) {
            $expectedDescription = new StringDescription();
            $actualDescription = new StringDescription();

            $matcher->describeTo($expectedDescription);
            DescriptionUtils::describe($this->publishedEvents, $actualDescription);

            throw new GovernorAssertionError(
                sprintf(
                    "Published events did not match.\nExpected:\n<%s>\n\nGot:\n<%s>\n",
                    $expectedDescription,
                    $actualDescription
                )
            );
        }
    }

    /**
     * Assert that the given <code>expected</code> events have been published.
     *
     * @param array $expected the events that must have been published.
     * @throws GovernorAssertionError
     */
    public function assertPublishedEvents(array $expected = array())
    {
        if (count($this->publishedEvents) !== count($expected)) {
            throw new GovernorAssertionError(
                sprintf(
                    "Got wrong number of events published. Expected <%s>, got <%s>",
                    count($expected),
                    count($this->publishedEvents)
                )
            );
        }

        $this->assertPublishedEventsMatching(
            Matchers::payloadsMatching(Matchers::exactSequenceOf($this->createEqualToMatchers($expected)))
        );
    }


    public function handle(EventMessageInterface $event)
    {
        $this->publishedEvents[] = $event;
    }

    /**
     * Starts recording event published by the event bus.
     */
    public function startRecording()
    {
        $this->eventBus->getEventListenerRegistry()->subscribe($this);

    }


    private function createEqualToMatchers(array $expected)
    {
        $matchers = array();

        foreach ($expected as $event) {
            $matchers[] = Matchers::equalTo($event);
        }

        return $matchers;
    }
}