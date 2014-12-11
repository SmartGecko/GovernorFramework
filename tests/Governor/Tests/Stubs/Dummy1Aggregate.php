<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Tests\Stubs;

use Governor\Framework\EventSourcing\AbstractEventSourcedAggregateRoot;
use Governor\Framework\Domain\DomainEventMessageInterface;
use Governor\Framework\Annotations\CommandHandler;

/**
 * Description of DummyAggregate
 *
 * @author 255196
 */
class Dummy1Aggregate extends AbstractEventSourcedAggregateRoot
{

    private $id;
    
    /**
     * @CommandHandler 
     */
    public function __construct(CreateDummy1Command $command)
    {
        ;
    }

    protected function getChildEntities()
    {
        return null;
    }

    public function getIdentifier()
    {
        return $this->id;
    }

    protected function handle(DomainEventMessageInterface $event)
    {
        
    }

}

class CreateDummy1Command
{

    private $id;

    function __construct($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

}
