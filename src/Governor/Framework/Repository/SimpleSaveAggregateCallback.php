<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Repository;

use Governor\Framework\Domain\AggregateRootInterface;
use Governor\Framework\UnitOfWork\SaveAggregateCallbackInterface;

/**
 * Description of SimpleSaveAggregateCallback
 *
 * @author david
 */
class SimpleSaveAggregateCallback implements SaveAggregateCallbackInterface
{

    private $closure;

    public function __construct(\Closure $closure)
    {
        $this->closure = $closure;
    }

    public function save(AggregateRootInterface $aggregate)
    {
        $cb = $this->closure;
        $cb($aggregate);
    }

}
