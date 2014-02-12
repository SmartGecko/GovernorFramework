<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\CommandHandling;

/**
 *
 * @author david
 */
interface CommandTargetResolverInterface
{

    /**
     * Returns the Aggregate Identifier and optionally the expected version of the aggregate on which the given {@code
     * command} should be executed. The version may be {@code null} if no specific version is required.
     *
     * @param command The command from which to extract the identifier and version
     * @return \Governor\Framework\CommandHandling\VersionedAggregateIdentifier
     *
     * @throws IllegalArgumentException if the command is not formatted correctly to extract this information
     */
    public function resolveTarget(CommandMessageInterface $command);
}
