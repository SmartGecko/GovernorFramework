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

namespace Governor\Tests\UnitOfWork;

use Governor\Framework\UnitOfWork\CurrentUnitOfWork;
use Governor\Framework\UnitOfWork\UnitOfWorkInterface;
use Governor\Framework\UnitOfWork\DefaultUnitOfWork;

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
        $mockUnitOfWork = $this->getMock(UnitOfWorkInterface::class);
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
