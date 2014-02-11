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

    /*
      public void tearDown() {
      while (CurrentUnitOfWork.isStarted()) {
      CurrentUnitOfWork.get().rollback();
      }
      }
     */

//      @Test(expected = IllegalStateException.class)
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

    
    /*
      public function testNotCurrentUnitOfWorkCommitted() {
      $outerUoW = new DefaultUnitOfWork();
      outerUoW.start();
      new DefaultUnitOfWork().start();
      try {
      outerUoW.commit();
      } catch (IllegalStateException e) {
      assertTrue("Wrong type of message: " + e.getMessage(), e.getMessage().contains("not the active"));
      }
      } */
}
