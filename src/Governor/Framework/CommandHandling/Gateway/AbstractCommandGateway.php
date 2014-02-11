<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\CommandHandling\Gateway;

use Governor\Framework\Domain\MetaData;
use Governor\Framework\CommandHandling\CommandCallback;
use Governor\Framework\CommandHandling\CommandBusInterface;
use Governor\Framework\CommandHandling\GenericCommandMessage;

/**
 * Description of AbstractCommandGateway
 *
 * @author david
 */
class AbstractCommandGateway implements CommandGatewayInterface
{

    private $commandBus;

    public function __construct(CommandBusInterface $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    public function send($command, CommandCallback $callback = null)
    {
        $message = new GenericCommandMessage($command, MetaData::emptyInstance());
       
        $this->commandBus->dispatch($message, $callback);
    }

}
