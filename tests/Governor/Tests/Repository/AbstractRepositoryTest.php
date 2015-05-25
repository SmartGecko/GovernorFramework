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

use Governor\Framework\UnitOfWork\DefaultUnitOfWork;
use Governor\Framework\UnitOfWork\CurrentUnitOfWork;
use Governor\Framework\Domain\AbstractAggregateRoot;
use Governor\Framework\EventHandling\EventBusInterface;
use Governor\Framework\Repository\AbstractRepository;

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
        $this->mockEventBus = $this->getMock(EventBusInterface::class);
        $this->testSubject = new MockAbstractRepository(MockAggregateRoot::class,
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

    protected function doLoad($id, $expectedVersion)
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
