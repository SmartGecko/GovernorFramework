<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Saga;

use Governor\Framework\Domain\EventMessageInterface;
/**
 * Description of GenericSagaFactoryTest
 *
 * @author david
 */
class GenericSagaFactoryTest extends \PHPUnit_Framework_TestCase
{

    private $testSubject;

    public function setUp()
    {
        $this->testSubject = new GenericSagaFactory();
    }

    public function testSupports()
    {
        $this->assertTrue($this->testSubject->supports('Governor\Framework\Saga\SupportedSaga'));
        $this->assertFalse($this->testSubject->supports('Governor\Framework\Saga\UnsupportedSaga'));
    }

    public function testCreateInstance_Supported()
    {
        $this->assertNotNull($this->testSubject->createSaga('Governor\Framework\Saga\SupportedSaga'));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testCreateInstance_Unsupported()
    {
        $this->testSubject->createSaga('Governor\Framework\Saga\UnsupportedSaga');
    }

}

class SupportedSaga implements SagaInterface
{

    public function getSagaIdentifier()
    {
        return "supported";
    }

    public function getAssociationValues()
    {
        return new AssociationValuesImpl();
    }

    public function handle(EventMessageInterface $event)
    {
        
    }

    public function isActive()
    {
        return true;
    }

}

class UnsupportedSaga
{
    
}
