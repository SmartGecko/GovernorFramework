<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\CommandHandling;

use Governor\Framework\UnitOfWork\UnitOfWorkInterface;
use Governor\Framework\Repository;

/**
 * Description of AnnotatedAggregateCommandHandler
 *
 * @author david
 */
class AnnotatedAggregateCommandHandler implements CommandHandlerInterface
{

    private $repository;

    function __construct(Repository $repository)
    {
        $this->repository = $repository;
    }

    public function handle(CommandMessageInterface $commandMessage,
        UnitOfWorkInterface $unitOfWork)
    {
        
    }

}
