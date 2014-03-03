<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\UnitOfWork;

/**
 * Description of CurrentUnitOfWork
 *
 * @author david
 */
abstract class CurrentUnitOfWork
{

    private static $current = array();

    public static function isStarted()
    {
        return !empty(self::$current);
    }

    /**
     * 
     * @return \Governor\Framework\UnitOfWork\UnitOfWorkInterface
     * @throws \RuntimeException
     */
    public static function get()
    {
        if (self::isEmpty()) {
            throw new \RuntimeException("No UnitOfWork is currently started");
        }

        return reset(self::$current);
    }

    private static function isEmpty()
    {
        $unitsOfWork = self::$current;
        return null === $unitsOfWork || empty($unitsOfWork);
    }

    public static function commit()
    {
        self::get()->commit();
    }

    /**
     * Binds the given <code>unitOfWork</code> to the current thread. If other UnitOfWork instances were bound, they
     * will be marked as inactive until the given UnitOfWork is cleared.
     *
     * @param unitOfWork The UnitOfWork to bind to the current thread.
     */
    public static function set(UnitOfWorkInterface $unitOfWork)
    {                
        self::$current[] = $unitOfWork;
    }

    public static function clear(UnitOfWorkInterface $unitOfWork)
    {        
        if (end(self::$current) === $unitOfWork) {
            $current = array_pop(self::$current);
        } else {
            throw new \RuntimeException("Could not clear this UnitOfWork. It is not the active one.");
        }
    }

}
