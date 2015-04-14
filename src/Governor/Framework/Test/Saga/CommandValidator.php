<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 19/12/14
 * Time: 20:44
 */

namespace Governor\Framework\Test\Saga;

use Governor\Framework\CommandHandling\CommandMessageInterface;
use Governor\Framework\Test\GovernorAssertionError;
use Governor\Framework\Test\Matchers\EqualFieldsMatcher;
use Governor\Framework\Test\Utils\RecordingCommandBus;
use Hamcrest\Matcher;
use Hamcrest\StringDescription;

class CommandValidator
{

    /**
     * @var RecordingCommandBus
     */
    private $commandBus;

    /**
     * Creates a validator which monitors the given <code>commandBus</code>.
     *
     * @param RecordingCommandBus $commandBus the command bus to monitor
     */
    public function __construct(RecordingCommandBus $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    /**
     * Starts recording commands on the command bus.
     */
    public function startRecording()
    {
        $this->commandBus->clearCommands();
    }

    /**
     * Assert that the given commands have been dispatched in the exact sequence provided.
     *
     * @param array $expected The commands expected to have been published on the bus
     * @throws GovernorAssertionError
     */
    public function assertDispatchedEqualTo(array $expected = array())
    {
        $actual = $this->commandBus->getDispatchedCommands();

        if (count($expected) !== count($actual)) {
            throw new GovernorAssertionError(
                sprintf(
                    "Got wrong number of commands dispatched. Expected <%s>, got <%s>",
                    count($expected),
                    count($actual)
                )
            );
        }

        $actualIterator = new \ArrayIterator($actual);
        $expectedIterator = new \ArrayIterator($expected);
        $counter = 0;

        while ($actualIterator->valid()) {
            $actualItem = $actualIterator->current();
            $expectedItem = $expectedIterator->current();

            if ($expectedItem instanceof CommandMessageInterface) {
                if ($expectedItem->getPayloadType() !== $actualItem->getPayloadType()) {
                    throw new GovernorAssertionError(
                        sprintf(
                            "Unexpected payload type of command at position %s (0-based). Expected <%s>, got <%s>",
                            $counter,
                            $expectedItem->getPayloadType(),
                            $actualItem->getPayloadType()
                        )
                    );
                }

                $this->assertCommandEquality($counter, $expectedItem->getPayloadType(), $actualItem->getPayload());

                if ($expectedItem->getMetaData() !== $actualItem->getMetaData()) {
                    throw new GovernorAssertionError(
                        sprintf(
                            "Unexpected Meta Data of command at position %s (0-based). Expected <%s>, got <%s>",
                            $counter,
                            $expectedItem->getMetaData(),
                            $actualItem->getMetaData()
                        )
                    );
                }

            } else {
                $this->assertCommandEquality($counter, $expectedItem, $actualItem->getPayload());
            }

            $counter++;
            $actualIterator->next();
            $expectedIterator->next();
        }
    }

    /**
     * Assert that commands matching the given <code>matcher</code> has been dispatched on the command bus.
     *
     * @param Matcher $matcher The matcher validating the actual commands
     * @throws GovernorAssertionError
     */
    public function assertDispatchedMatching(Matcher $matcher)
    {
        if (!$matcher->matches($this->commandBus->getDispatchedCommands())) {

            $expectedDescription = new StringDescription();
            $actualDescription = new StringDescription();
            $matcher->describeTo($expectedDescription);
            DescriptionUtils::describe($this->commandBus->getDispatchedCommands(), $actualDescription);

            throw new GovernorAssertionError(
                sprintf(
                    "Incorrect dispatched command. Expected <%s>, but got <%s>",
                    $expectedDescription,
                    $actualDescription
                )
            );
        }
    }

    private function assertCommandEquality($commandIndex, $expected, $actual)
    {
        if ($expected !== $actual) {
            if (get_class($expected) !== get_class($actual)) {
                throw new GovernorAssertionError(
                    sprintf(
                        "Wrong command type at index %s (0-based). Expected <%s>, but got <%s>",
                        $commandIndex,
                        get_class($expected),
                        get_class($actual)
                    )
                );
            }
        }

        $matcher = new EqualFieldsMatcher($expected);

        if (!$matcher->matches($actual)) {
            throw new GovernorAssertionError(
                sprintf(
                    "Unexpected command at index %s (0-based). "
                    . "Field value of '%s.%s', expected <%s>, but got <%s>",
                    $commandIndex,
                    get_class($expected),
                    $matcher->getFailedField(),
                    $matcher->getFailedFieldExpectedValue(),
                    $matcher->getFailedFieldActualValue()
                )
            );
        }

    }
}