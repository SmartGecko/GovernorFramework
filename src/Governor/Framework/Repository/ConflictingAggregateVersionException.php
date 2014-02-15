<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Repository;

/**
 * Description of ConflictingAggregateVersionException
 *
 * @author david
 */
class ConflictingAggregateVersionException extends \RuntimeException
{

    private $aggregateIdentifier;
    private $expectedVersion;
    private $actualVersion;

    public function __construct($id, $expectedVersion, $actualVersion)
    {
        parent::__construct(sprintf("The version of aggregate [%s] was not as expected. "
                . "Expected [%s], but repository found [%s]", $id,
                $expectedVersion, $actualVersion));
        $this->aggregateIdentifier = $id;
        $this->expectedVersion = $expectedVersion;
        $this->actualVersion = $actualVersion;
    }

    public function getAggregateIdentifier()
    {
        return $this->aggregateIdentifier;
    }

    public function getExpectedVersion()
    {
        return $this->expectedVersion;
    }

    public function getActualVersion()
    {
        return $this->actualVersion;
    }


}
