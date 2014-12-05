<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

use Governor\Framework\Annotations as Governor;

use Governor\Framework\Domain\MetaData;

/**
 * Description of ParameterResolving
 *
 * @author 255196
 */
class ParameterResolving {
    
    
    /**
     * @Governor\CommandHandler
     * @Governor\Resolve(parameter="service", @Governor\Inject("service.name"))
     * @Governor\Resolve(parameter="userIdentifier", @Governor\MetaData)
     * @param Command $command
     */
    public function doSomething(Command $command, Service $service, MetaData $metadata, $userIdentifier)
    {
        
    }
    
    public function onSomethig(Event $event)
    {
        
    }
}

class Service {}
class Event {}

class Command {}