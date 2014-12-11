<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Tests\Saga\Annotation;

use Governor\Framework\Saga\AssociationValue;
use Governor\Framework\Saga\Annotation\AssociationValuesImpl;

/**
 * Description of AssociationValuesImplTest
 */
class AssociationValuesImplTest extends \PHPUnit_Framework_TestCase
{

    private $testSubject;
    private $associationValue;

    public function setUp()
    {
        $this->testSubject = new AssociationValuesImpl();
        $this->associationValue = new AssociationValue("key", "value");
    }

    public function testAddAssociationValue()
    {
        $this->testSubject->add($this->associationValue);

        $this->assertCount(1, $this->testSubject->addedAssociations());
        $this->assertEmpty($this->testSubject->removedAssociations());
    }

    public function testAddAssociationValue_AddedTwice()
    {
        $this->testSubject->add($this->associationValue);
        $this->testSubject->commit();
        $this->testSubject->add($this->associationValue);
        $this->assertEmpty($this->testSubject->addedAssociations());
        $this->assertEmpty($this->testSubject->removedAssociations());
    }

    public function testRemoveAssociationValue()
    {
        $this->assertTrue($this->testSubject->add($this->associationValue));
        $this->testSubject->commit();
        $this->assertTrue($this->testSubject->remove($this->associationValue));
        $this->assertEmpty($this->testSubject->addedAssociations());
        $this->assertCount(1, $this->testSubject->removedAssociations());
    }

    public function testRemoveAssociationValue_NotInContainer()
    {
        $this->testSubject->remove($this->associationValue);
        $this->assertEmpty($this->testSubject->addedAssociations());
        $this->assertEmpty($this->testSubject->removedAssociations());
    }

    public function testAddAndRemoveEntry()
    {
        $this->testSubject->add($this->associationValue);
        $this->testSubject->remove($this->associationValue);

        $this->assertEmpty($this->testSubject->addedAssociations());
        $this->assertEmpty($this->testSubject->removedAssociations());
    }

    public function testContains()
    {
        $this->assertFalse($this->testSubject->contains($this->associationValue));
        $this->testSubject->add($this->associationValue);
        $this->assertTrue($this->testSubject->contains($this->associationValue));

        $this->assertTrue($this->testSubject->contains(new AssociationValue("key",
                        "value")));
        $this->testSubject->remove($this->associationValue);
        $this->assertFalse($this->testSubject->contains($this->associationValue));
    }

}
