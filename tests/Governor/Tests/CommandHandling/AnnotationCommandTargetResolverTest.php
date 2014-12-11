<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Tests\CommandHandling;

use Rhumsaa\Uuid\Uuid;
use Governor\Framework\CommandHandling\AnnotationCommandTargetResolver;
use Governor\Framework\Annotations\TargetAggregateIdentifier;
use Governor\Framework\Annotations\TargetAggregateVersion;
use Governor\Framework\CommandHandling\GenericCommandMessage;

class AnnotationCommandTargetResolverTest extends \PHPUnit_Framework_TestCase
{

    private $testSubject;

    public function setUp()
    {
        $this->testSubject = new AnnotationCommandTargetResolver();
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testResolveTarget_CommandWithoutAnnotations()
    {
        $this->testSubject->resolveTarget(GenericCommandMessage::asCommandMessage(new NotAnnotatedCommand()));
    }

    public function testResolveTarget_WithAnnotatedMethod()
    {
        $aggregateIdentifier = Uuid::uuid1();
        $actual = $this->testSubject->resolveTarget(GenericCommandMessage::asCommandMessage(new MethodAnnotatedCommand($aggregateIdentifier,
                        null)));

        $this->assertSame($aggregateIdentifier, $actual->getIdentifier());
        $this->assertNull($actual->getVersion());
    }

    public function testResolveTarget_WithAnnotatedMethodAndVersion()
    {
        $aggregateIdentifier = Uuid::uuid1();
        $actual = $this->testSubject->resolveTarget(GenericCommandMessage::asCommandMessage(new MethodAnnotatedCommand($aggregateIdentifier,
                        1)));

        $this->assertSame($aggregateIdentifier, $actual->getIdentifier());
        $this->assertEquals(1, $actual->getVersion());
    }

    public function testResolveTarget_WithAnnotatedFields()
    {
        $aggregateIdentifier = Uuid::uuid1();
        $version = 1;
        $actual = $this->testSubject->resolveTarget(GenericCommandMessage::asCommandMessage(new FieldAnnotatedCommand($aggregateIdentifier,
                        $version)));
        $this->assertEquals($aggregateIdentifier, $actual->getIdentifier());
        $this->assertEquals($version, $actual->getVersion());
    }

    /*

      @Test(expected = IllegalArgumentException.class)
      public void testResolveTarget_WithAnnotatedFields_NonNumericVersion() {
      final UUID aggregateIdentifier = UUID.randomUUID();
      final Object version = "abc";
      testSubject.resolveTarget(asCommandMessage(new FieldAnnotatedCommand(aggregateIdentifier, version)));
      }

      } */
}

class FieldAnnotatedCommand
{

    /**
     * @TargetAggregateIdentifier
     */
    private $aggregateIdentifier;

    /**
     * @TargetAggregateVersion
     */
    private $version;

    public function __construct($aggregateIdentifier, $version)
    {
        $this->aggregateIdentifier = $aggregateIdentifier;
        $this->version = $version;
    }

    public function getAggregateIdentifier()
    {
        return $this->aggregateIdentifier;
    }

    public function getVersion()
    {
        return $this->version;
    }

}

class NotAnnotatedCommand
{
    
}

class MethodAnnotatedCommand
{

    private $aggregateIdentifier;
    private $version;

    public function __construct($aggregateIdentifier, $version)
    {
        $this->aggregateIdentifier = $aggregateIdentifier;
        $this->version = $version;
    }

    /**
     * @TargetAggregateIdentifier
     */
    public function getAggregateIdentifier()
    {
        return $this->aggregateIdentifier;
    }

    /**
     * @TargetAggregateVersion
     */
    public function getVersion()
    {
        return $this->version;
    }

}
