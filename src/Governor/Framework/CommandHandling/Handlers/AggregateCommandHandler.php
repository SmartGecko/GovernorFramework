<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\CommandHandling\Handlers;

use Governor\Framework\CommandHandling\CommandHandlerInterface;
use Governor\Framework\CommandHandling\CommandMessageInterface;
use Governor\Framework\UnitOfWork\UnitOfWorkInterface;

/**
 * Description of AggregateCommandHandler
 *
 * @author 255196
 */
class AggregateCommandHandler implements CommandHandlerInterface
{

    public function handle(CommandMessageInterface $commandMessage, UnitOfWorkInterface $unitOfWork)
    {
        
    }

}
