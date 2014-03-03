<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\UnitOfWork;

/**
 * Description of CurrentUnitOfWorkTest
 *
 * @author david
 */
class CurrentUnitOfWorkTest extends \PHPUnit_Framework_TestCase
{

    public function setUp()
    {
        while (CurrentUnitOfWork::isStarted()) {
            CurrentUnitOfWork::get()->rollback();
        }
    }

    public function tearDown()
    {
        while (CurrentUnitOfWork::isStarted()) {
            CurrentUnitOfWork::get()->rollback();
        }
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testGetSession_NoCurrentSession()
    {
        CurrentUnitOfWork::get();
    }

    public function testSetSession()
    {
        $mockUnitOfWork = $this->getMock('Governor\Framework\UnitOfWork\UnitOfWorkInterface');
        CurrentUnitOfWork::set($mockUnitOfWork);

        $this->assertSame($mockUnitOfWork, CurrentUnitOfWork::get());

        CurrentUnitOfWork::clear($mockUnitOfWork);
        $this->assertFalse(CurrentUnitOfWork::isStarted());
    }

    public function testNotCurrentUnitOfWorkCommitted()
    {
        $outerUoW = new DefaultUnitOfWork();
        $outerUoW->start();

        $other = new DefaultUnitOfWork();
        $other->start();

        try {
            $outerUoW->commit();
        } catch (\Exception $ex) {
            $other->rollback();
            $this->assertGreaterThanOrEqual(0,
                strpos($ex->getMessage(), "not the active"));
        }
    }

}
