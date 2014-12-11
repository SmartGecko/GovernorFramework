<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Tests\Common;

use Governor\Framework\Common\ReflectionUtils;

/**
 * Description of ReflectionUtilsTest
 *
 * @author david
 */
class ReflectionUtilsTest extends \PHPUnit_Framework_TestCase
{

    private $testSubjectA;
    private $testSubjectB;

    public function setUp()
    {
        $this->testSubjectA = new A;
        $this->testSubjectB = new B;
    }

    public function testReturnsAllProperties()
    {
        $reflClassA = new \ReflectionClass($this->testSubjectA);
        $reflClassB = new \ReflectionClass($this->testSubjectB);

        $this->assertCount(1, ReflectionUtils::getProperties($reflClassA));
        $this->assertCount(2, ReflectionUtils::getProperties($reflClassB));
    }

    public function testReturnsAllPublicMethods()
    {
        $reflClassA = new \ReflectionClass($this->testSubjectA);
        $reflClassB = new \ReflectionClass($this->testSubjectB);

        $this->assertCount(1, ReflectionUtils::getMethods($reflClassA));
        $this->assertCount(2, ReflectionUtils::getMethods($reflClassB));
    }

}

class A
{

    private $one;

    public function getOne()
    {
        
    }

}

class B extends A
{

    private $two;

    public function getTwo()
    {
        
    }

}
