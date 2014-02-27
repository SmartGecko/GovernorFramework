<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Common;

/**
 * Description of IdentifierValidator
 */
class IdentifierValidator
{

    /**
     * Validates the identifier. Currently only scalar PHP types are supported as aggregate identifiers.
     * 
     * @param mixed $identifier
     * @throws \InvalidArgumentException
     */
    public static function validateIdentifier($identifier)
    {
        if (!is_scalar($identifier)) {
            throw new \InvalidArgumentException(sprintf("One of the events contains an unsuitable aggregate identifier " .
                    "for this EventStore implementation. See reference guide " .
                    "for more information. Invalid type is: %s",
                    self::getType($identifier)));
        }
    }

    private static function getType($var)
    {
        if (is_object($var)) {
            return get_class($var);
        }

        return gettype($var);
    }

}
