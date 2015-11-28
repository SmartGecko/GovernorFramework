<?php

/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * The software is based on the Axon Framework project which is
 * licensed under the Apache 2.0 license. For more information on the Axon Framework
 * see <http://www.axonframework.org/>.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.governor-framework.org/>.
 */

namespace Governor\Tests\Repository;

use Ramsey\Uuid\Uuid;
use Governor\Tests\Stubs\StubAggregate;
use Governor\Framework\Repository\OptimisticLockManager;

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
