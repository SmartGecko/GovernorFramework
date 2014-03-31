<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\CommandHandling\Gateway;

use Governor\Framework\Domain\MetaData;
use Governor\Framework\CommandHandling\CommandCallbackInterface;

/**
 *
 * @author david
 */
interface CommandGatewayInterface
{

    /**
     * Sends the given <code>command</code>, and have the result of the command's execution reported to the given
     * <code>callback</code>.
     * <p/>
     * The given <code>command</code> is wrapped as the payload of the CommandMessage that is eventually posted on the
     * Command Bus, unless Command already implements {@link org.axonframework.domain.Message}. In that case, a
     * CommandMessage is constructed from that message's payload and MetaData.
     *
     * @param command  The command to dispatch
     * @param callback The callback to notify when the command has been processed
     * @param <R>      The type of result expected from command execution
     */
    public function send($command, CommandCallbackInterface $callback = null,
            MetaData $metaData = null);

    /**
     * Sends the given <code>command</code> and wait for it to execute. The result of the execution is returned when
     * available. This method will block indefinitely, until a result is available, or until the Thread is interrupted.
     * When the thread is interrupted, this method returns <code>null</code>. If command execution resulted in an
     * exception, it is wrapped in a {@link org.axonframework.commandhandling.CommandExecutionException}.
     * <p/>
     * The given <code>command</code> is wrapped as the payload of the CommandMessage that is eventually posted on the
     * Command Bus, unless Command already implements {@link org.axonframework.domain.Message}. In that case, a
     * CommandMessage is constructed from that message's payload and MetaData.
     * <p/>
     * Note that the interrupted flag is set back on the thread if it has been interrupted while waiting.
     *
     * @param command The command to dispatch
     * @param <R>     The type of result expected from command execution
     * @return the result of command execution, or <code>null</code> if the thread was interrupted while waiting for
     *         the
     *         command to execute
     *
     * @throws org.axonframework.commandhandling.CommandExecutionException
     *          when an exception occurred while processing the command
     */
    public function sendAndWait($command, MetaData $metaData = null);
}
