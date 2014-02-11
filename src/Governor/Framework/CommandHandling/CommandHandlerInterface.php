<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\CommandHandling;

use Governor\Framework\UnitOfWork\UnitOfWorkInterface;
/**
 *
 * @author david
 */
interface CommandHandlerInterface
{

    public function handle(CommandMessageInterface $commandMessage,
        UnitOfWorkInterface $unitOfWork);
}
