<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Repository;

use Rhumsaa\Uuid\Uuid;
use Governor\Framework\Stubs\StubAggregate;

class OptimisticLockManagerTest extends \PHPUnit_Framework_TestCase
{

    public function testLockReferenceCleanedUpAtUnlock()
    {
        $manager = new OptimisticLockManager();
        $identifier = Uuid::uuid1()->toString();

        $manager->obtainLock($identifier);
        $manager->releaseLock($identifier);

        $reflProperty = new \ReflectionProperty($manager, 'locks');
        $reflProperty->setAccessible(true);
        
        $this->assertEquals (0, count($reflProperty->getValue($manager)));      
    }

    public function testLockFailsOnConcurrentModification()
    {
        $identifier = Uuid::uuid1()->toString();
        $aggregate1 = new StubAggregate($identifier);
        $aggregate2 = new StubAggregate($identifier);

        $manager = new OptimisticLockManager();
        $manager->obtainLock($aggregate1->getIdentifier());
        $manager->obtainLock($aggregate2->getIdentifier());

        $aggregate1->doSomething();
        $aggregate2->doSomething();
        
        $this->assertTrue($manager->validateLock($aggregate1));
        $this->assertFalse($manager->validateLock($aggregate2));
    }

}
