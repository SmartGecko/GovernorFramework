<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\CommandHandling;

use Rhumsaa\Uuid\Uuid;
use Governor\Framework\Domain\MetaData;

/**
 * Description of GenericCommandMessage
 *
 * @author david
 */
class GenericCommandMessage implements CommandMessageInterface
{

    private $id;
    private $commandName;
    private $payload;
    private $metaData;

    public function __construct($payload, MetaData $metaData, $id = null,
        $commandName = null)
    {
        $this->id = (null === $id) ? Uuid::uuid1()->toString() : $id;
        $this->commandName = (null === $commandName) ? get_class($payload) : $commandName;
        $this->payload = $payload;
        $this->metaData = (null === $metaData) ? MetaData::emptyInstance() : $metaData;
    }

    public static function asCommandMessage($command)
    {
        if (!is_object($command)) {
            throw new \InvalidArgumentException(sprintf('Commands must be objects but recieved an %s',
                get_type($command)));
        }

        if ($command instanceof CommandMessageInterface) {
            return $command;
        }

        return new GenericCommandMessage($command, MetaData::emptyInstance());
    }

    public function andMetaData(array $metadata = array())
    {
        if (empty($metadata)) {
            return $this;
        }
        return new GenericCommandMessage($this->payload,
            $this->metaData->mergedWith($metadata), $this->id,
            $this->commandName);
    }

    public function getCommandName()
    {
        return $this->commandName;
    }

    public function getIdentifier()
    {
        return $this->id;
    }

    public function getMetaData()
    {
        return $this->metaData;
    }

    public function getPayload()
    {
        return $this->payload;
    }

    public function getPayloadType()
    {
        return get_class($this->payload);
    }

    public function withMetaData(array $metadata = array())
    {
        if ($this->metaData->isEqualTo($metadata)) {
            return $this;
        }
        return new GenericCommandMessage($this->payload,
            new MetaData($metadata), $this->id, $this->commandName);
    }

}
