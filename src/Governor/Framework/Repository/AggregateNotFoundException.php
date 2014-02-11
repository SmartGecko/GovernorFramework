<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Repository;

/**
 * Description of AggregateNotFoundException
 *
 * @author david
 */
class AggregateNotFoundException extends \Exception
{

    private $aggregateId;

    public function __construct($aggregateId, $message)
    {
        parent::__construct($message);
        $this->aggregateId = $aggregateId;
    }

    public function getAggregateId()
    {
        return $this->aggregateId;
    }

}
