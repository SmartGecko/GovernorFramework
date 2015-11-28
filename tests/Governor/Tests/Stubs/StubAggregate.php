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

namespace Governor\Tests\Stubs;

use Ramsey\Uuid\Uuid;
use Governor\Framework\EventSourcing\AbstractEventSourcedAggregateRoot;
use Governor\Framework\Domain\MetaData;
use Governor\Framework\Domain\DomainEventMessageInterface;
use Governor\Framework\Domain\GenericDomainEventMessage;

class StubAggregate extends AbstractEventSourcedAggregateRoot
{

    private $invocationCount;
    private $identifier;

    public function __construct($id = null)
    {
        $this->identifier = (null === $id) ? Uuid::uuid1()->toString() : $id;        
    }

    public function doSomething()
    {
        $this->apply(new StubDomainEvent());
    }

    public function getIdentifier()
    {
        return $this->identifier;
    }

    protected function handle(DomainEventMessageInterface $event)
    {        
        $this->identifier = $event->getAggregateIdentifier();
        $this->invocationCount++;
    }

    public function getInvocationCount()
    {
        return $this->invocationCount;
    }

    public function createSnapshotEvent()
    {        
        return new GenericDomainEventMessage($this->getIdentifier(), 5,
            new StubDomainEvent(), MetaData::emptyInstance());
    }

    public function delete()
    {
        $this->apply(new StubDomainEvent());
        $this->markDeleted();
    }

    protected function getChildEntities()
    {
        return null;
    }

}
