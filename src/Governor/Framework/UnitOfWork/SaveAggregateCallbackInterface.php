<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\UnitOfWork;

use Governor\Framework\Domain\AggregateRootInterface;

/**
 *
 * @author david
 */
interface SaveAggregateCallbackInterface
{

    public function save(AggregateRootInterface $aggregate);
}
