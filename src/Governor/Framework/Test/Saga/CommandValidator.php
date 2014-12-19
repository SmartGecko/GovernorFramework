<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 19/12/14
 * Time: 20:44
 */

namespace Governor\Framework\Test\Saga;

use Governor\Framework\Test\Utils\RecordingCommandBus;
use Hamcrest\Matcher;

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
     */
    public function assertDispatchedEqualTo(array $expected)
    {
        /* List<CommandMessage<?>> actual = commandBus.getDispatchedCommands();
             if (actual.size() != expected.length) {
                 throw new AxonAssertionError(format(
                         "Got wrong number of commands dispatched. Expected <%s>, got <%s>",
                         expected.length,
                         actual.size()));
             }
             Iterator<CommandMessage<?>> actualIterator = actual.iterator();
             Iterator<Object> expectedIterator = Arrays.asList(expected).iterator();

             int counter = 0;
             while (actualIterator.hasNext()) {
                 CommandMessage<?> actualItem = actualIterator.next();
                 Object expectedItem = expectedIterator.next();
                 if (expectedItem instanceof CommandMessage) {
                     CommandMessage<?> expectedMessage = (CommandMessage<?>) expectedItem;
                     if (!expectedMessage.getPayloadType().equals(actualItem.getPayloadType())) {
                         throw new AxonAssertionError(format(
                                 "Unexpected payload type of command at position %s (0-based). Expected <%s>, got <%s>",
                                 counter,
                                 expectedMessage.getPayloadType(),
                                 actualItem.getPayloadType()));
                     }
                     assertCommandEquality(counter, expectedMessage.getPayload(), actualItem.getPayload());
                     if (!expectedMessage.getMetaData().equals(actualItem.getMetaData())) {
                         throw new AxonAssertionError(format(
                                 "Unexpected Meta Data of command at position %s (0-based). Expected <%s>, got <%s>",
                                 counter,
                                 expectedMessage.getMetaData(),
                                 actualItem.getMetaData()));
                     }
                 } else {
                     assertCommandEquality(counter, expectedItem, actualItem.getPayload());
                 }
                 counter++;
             }*/
    }

    /**
     * Assert that commands matching the given <code>matcher</code> has been dispatched on the command bus.
     *
     * @param Matcher $matcher The matcher validating the actual commands
     */
    public function assertDispatchedMatching(Matcher $matcher)
    {
        /*    if (!matcher.matches(commandBus.getDispatchedCommands())) {
                Description expectedDescription = new StringDescription();
        Description actualDescription = new StringDescription();
        matcher.describeTo(expectedDescription);
        describe(commandBus.getDispatchedCommands(), actualDescription);
        throw new AxonAssertionError(format("Incorrect dispatched command. Expected <%s>, but got <%s>",
            expectedDescription, actualDescription));
    }*/
    }

    private function assertCommandEquality($commandIndex, $expected, $actual)
    {
        /*   if (!expected.equals(actual)) {
               if (!expected.getClass().equals(actual.getClass())) {
                   throw new AxonAssertionError(format("Wrong command type at index %s (0-based). "
                       + "Expected <%s>, but got <%s>",
                       commandIndex,
                       expected.getClass().getSimpleName(),
                       actual.getClass().getSimpleName()));
               }
               EqualFieldsMatcher<Object> matcher = new EqualFieldsMatcher<Object>(expected);
       if (!matcher.matches(actual)) {
           throw new AxonAssertionError(format("Unexpected command at index %s (0-based). "
               + "Field value of '%s.%s', expected <%s>, but got <%s>",
               commandIndex,
               expected.getClass().getSimpleName(),
               matcher.getFailedField().getName(),
               matcher.getFailedFieldExpectedValue(),
               matcher.getFailedFieldActualValue()));
       }
   }*/
    }
}