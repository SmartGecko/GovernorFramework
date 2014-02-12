<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\CommandHandling;

/**
 * Description of VersionedAggregateIdentifier
 *
 * @author david
 */
class VersionedAggregateIdentifier
{

    private $identifier;
    private $version;

    /**
     * Initializes a VersionedAggregateIdentifier with the given {@code identifier} and {@code version}.
     *
     * @param identifier The identifier of the targeted aggregate
     * @param version    The expected version of the targeted aggregate, or {@code null} if the version is irrelevant
     */
    public function __construct($identifier, $version)
    {
        $this->identifier = $identifier;
        $this->version = $version;
    }

    /**
     * Returns the identifier of the targeted Aggregate. May never return <code>null</code>.
     *
     * @return the identifier of the targeted Aggregate
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Returns the version of the targeted Aggregate, or {@code null} if the version is irrelevant.
     *
     * @return the version of the targeted Aggregate
     */
    public function getVersion()
    {
        return $this->version;
    }

}
