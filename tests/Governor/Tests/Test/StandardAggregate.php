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

namespace Governor\Tests\Test;

use Ramsey\Uuid\Uuid;
use Governor\Framework\Annotations\EventHandler;
use Governor\Framework\Annotations\AggregateIdentifier;
use Governor\Framework\Domain\DomainEventMessageInterface;
use Governor\Framework\EventSourcing\Annotation\AbstractAnnotatedAggregateRoot;

/**
 * Description of StandardAggregate
 *
 * @author david
 */
class StandardAggregate extends AbstractAnnotatedAggregateRoot
{

    private $counter;
    private $lastNumber;

    /**
     * @AggregateIdentifier     
     */
    private $identifier;
    private $entity;

    public function __construct($initialValue, $aggregateIdentifier = null)
    {
     //   $this->apply(new MyEvent($aggregateIdentifier === null ? Uuid::uuid1() : $aggregateIdentifier,
             //   $initialValue));
    }

    public function delete($withIllegalStateChange)
    {
        $this->apply(new MyAggregateDeletedEvent($withIllegalStateChange));
        if ($withIllegalStateChange) {
            $this->markDeleted();
        }
    }

    public function doSomethingIllegal($newIllegalValue)
    {
        $this->apply(new MyEvent($this->identifier, $this->lastNumber + 1));
        $this->lastNumber = $newIllegalValue;
    }

    /**
     *  @EventHandler
     */
    public function handleMyEvent(MyEvent $event)
    {
        $this->identifier = $event->getAggregateIdentifier();
        $this->lastNumber = $event->getSomeValue();
        if (null === $this->entity) {
            $this->entity = new MyEntity();
        }
    }

    /**
     *  @EventHandler
     */
    public function deleted(MyAggregateDeletedEvent $event)
    {
        if (!$event->isWithIllegalStateChange()) {
            $this->markDeleted();
        }
    }

    /**
     *  @EventHandler
     */
    public function handleAll(DomainEventMessageInterface $event)
    {
        // we don't care about events
    }

    public function doSomething()
    {
        // this state change should be accepted, since it happens on a transient value
        $this->counter++;
        $this->apply(new MyEvent($this->identifier, $this->lastNumber + 1));
    }

    public function getIdentifier()
    {
        return $this->identifier;
    }

    /*
      static class Factory extends AbstractAggregateFactory<StandardAggregate> {

      @Override
      protected StandardAggregate doCreateAggregate(Object aggregateIdentifier, DomainEventMessage firstEvent) {
      return new StandardAggregate(aggregateIdentifier);
      }

      @Override
      public String getTypeIdentifier() {
      return StandardAggregate.class.getSimpleName();
      }

      @Override
      public Class<StandardAggregate> getAggregateType() {
      return StandardAggregate.class;
      }
      } */
}
