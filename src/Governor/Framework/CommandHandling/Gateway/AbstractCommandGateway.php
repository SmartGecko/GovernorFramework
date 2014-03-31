<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\CommandHandling\Gateway;

use Governor\Framework\Domain\MetaData;
use Governor\Framework\CommandHandling\CommandCallbackInterface;
use Governor\Framework\CommandHandling\CommandBusInterface;
use Governor\Framework\CommandHandling\GenericCommandMessage;
use Governor\Framework\CommandHandling\Callbacks\ResultCallback;

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

    /**
     * {@inheritDoc}
     */
    public function send($command, CommandCallbackInterface $callback = null,
            MetaData $metaData = null)
    {
        $metaData = isset($metaData) ? $metaData : MetaData::emptyInstance();
        $message = new GenericCommandMessage($command, $metaData);

        $this->commandBus->dispatch($message, $callback);
    }

    /**
     * {@inheritDoc}
     */
    public function sendAndWait($command, MetaData $metaData = null)
    {
        $metaData = isset($metaData) ? $metaData : MetaData::emptyInstance();
        $message = new GenericCommandMessage($command, $metaData);
        $callback = new ResultCallback();

        $this->commandBus->dispatch($message, $callback);

        return $callback->getResult();
    }

}
