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

namespace Governor\Tests\Domain;

use Ramsey\Uuid\Uuid;
use Governor\Framework\Domain\GenericDomainEventMessage;
use Governor\Framework\Domain\MetaData;
use Governor\Framework\Domain\SimpleDomainEventStream;

/**
 * SimpleDomainEventStreamTest.
 * 
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a> 
 */
class SimpleDomainEventStreamTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers Governor\Framework\Domain\SimpleDomainEventStream::peek
     */
    public function testPeek()
    {
        $event1 = new GenericDomainEventMessage(Uuid::uuid1(), 0,
            new \stdClass(), new MetaData());
        $event2 = new GenericDomainEventMessage(Uuid::uuid1(), 0,
            new \stdClass(), new MetaData());
        $testSubject = new SimpleDomainEventStream(array($event1, $event2));
        $this->assertSame($event1, $testSubject->peek());
        $this->assertSame($event1, $testSubject->peek());
    }

    /**
     * @covers Governor\Framework\Domain\SimpleDomainEventStream::peek
     * @expectedException \OutOfBoundsException
     */
    public function testPeekEmptyStream()
    {
        $testSubject = new SimpleDomainEventStream();
        $this->assertFalse($testSubject->hasNext());

        $testSubject->peek();
    }

    /**
     * @covers Governor\Framework\Domain\SimpleDomainEventStream::hasNext
     * @covers Governor\Framework\Domain\SimpleDomainEventStream::next
     */
    public function testNextAndHasNext()
    {
        $event1 = new GenericDomainEventMessage(Uuid::uuid1(), 0,
            new \stdClass(), new MetaData());
        $event2 = new GenericDomainEventMessage(Uuid::uuid1(), 0,
            new \stdClass(), new MetaData());

        $testSubject = new SimpleDomainEventStream(array($event1, $event2));
        $this->assertTrue($testSubject->hasNext());
        $this->assertSame($event1, $testSubject->next());
        $this->assertTrue($testSubject->hasNext());
        $this->assertSame($event2, $testSubject->next());
        $this->assertFalse($testSubject->hasNext());
    }

    /**
     * @expectedException \OutOfBoundsException
     */
    public function testNextReadBeyondEnd()
    {
        $event1 = new GenericDomainEventMessage(Uuid::uuid1(), 0,
            new \stdClass(), new MetaData());
        $testSubject = new SimpleDomainEventStream(array($event1));
        $testSubject->next();
        $testSubject->next();
    }

}
