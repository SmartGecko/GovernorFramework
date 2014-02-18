<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Domain;

/**
 * Description of GenericEventMessage
 *
 * @author david
 */
class GenericEventMessage extends GenericMessage implements EventMessageInterface
{

    /**
     *
     * @var \DateTime
     */
    private $timestamp;

    public function __construct($payload, MetaData $metadata = null, $id = null,
            \DateTime $timestamp = null)
    {
        parent::__construct($payload, $metadata, $id);
        $this->timestamp = isset($timestamp) ? $timestamp : new \DateTime();
    }

    public function getTimestamp()
    {
        return $this->timestamp;
    }

}
