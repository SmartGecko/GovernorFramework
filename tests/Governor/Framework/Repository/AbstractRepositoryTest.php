<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Repository;

use Governor\Framework\UnitOfWork\DefaultUnitOfWork;
use Governor\Framework\UnitOfWork\CurrentUnitOfWork;
use Governor\Framework\Domain\AbstractAggregateRoot;

/**
 * Description of AbstractRepositoryTest
 *
 * @author david
 */
class AbstractRepositoryTest extends \PHPUnit_Framework_TestCase
{

    private $testSubject;
    private $mockEventBus;

    public function setUp()
    {
        $this->mockEventBus = $this->getMock('Governor\Framework\EventHandling\EventBusInterface');
        $this->testSubject = new MockAbstractRepository('Governor\Framework\Repository\MockAggregateRoot',
            $this->mockEventBus);
        DefaultUnitOfWork::startAndGet();
    }

    public function tearDown()
    {
        while (CurrentUnitOfWork::isStarted()) {
            CurrentUnitOfWork::get()->rollback();
        }
    }

    public function testAggregateTypeVerification_CorrectType()
    {
        $this->testSubject->add(new MockAggregateRoot("hi"));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testAggregateTypeVerification_WrongType()
    {
        $this->testSubject->add(new MockAnotherAggregateRoot("hi"));
    }

}

class MockAbstractRepository extends AbstractRepository
{

    protected function doDelete(\Governor\Framework\Domain\AggregateRootInterface $object)
    {
        
    }

    protected function doLoad($id, $exceptedVersion)
    {
        return new MockAggregateRoot();
    }

    protected function doSave(\Governor\Framework\Domain\AggregateRootInterface $object)
    {
        
    }

}

class MockAnotherAggregateRoot extends AbstractAggregateRoot
{

    private $id;

    function __construct($id)
    {
        $this->id = $id;
    }

    public function getIdentifier()
    {
        return $this->id;
    }

}

class MockAggregateRoot extends AbstractAggregateRoot
{

    private $id;

    function __construct($id)
    {
        $this->id = $id;
    }

    public function getIdentifier()
    {
        return $this->id;
    }

}
