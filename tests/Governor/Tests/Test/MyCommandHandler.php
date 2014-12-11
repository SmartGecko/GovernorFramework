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

use Governor\Framework\Annotations\CommandHandler;
use Governor\Framework\EventHandling\EventBusInterface;
use Governor\Framework\Repository\RepositoryInterface;
use Governor\Framework\Domain\GenericEventMessage;
use Governor\Framework\UnitOfWork\CurrentUnitOfWork;

/**
 * Description of MyCommandHandler
 *
 * @author david
 */
class MyCommandHandler
{

    private $repository;
    private $eventBus;

    public function __construct(RepositoryInterface $repository,
            EventBusInterface $eventBus)
    {
        $this->repository = $repository;
        $this->eventBus = $eventBus;
    }

    /**
     * @CommandHandler
     */
    public function createAggregate(CreateAggregateCommand $command)
    {
        $this->repository->add(new StandardAggregate(0,
                $command->getAggregateIdentifier()));
    }

    /**
     * @CommandHandler
     */
    public function handleTestCommand(TestCommand $testCommand)
    {
        $aggregate = $this->repository->load($testCommand->getAggregateIdentifier());
        $aggregate->doSomething();
    }

    /**
     * @CommandHandler
     */
    public function handleStrangeCommand(StrangeCommand $testCommand)
    {
        $aggregate = $this->repository->load($testCommand->getAggregateIdentifier(),
                null);
        $aggregate->doSomething();
        $this->eventBus->publish(new GenericEventMessage(new MyApplicationEvent()));
        CurrentUnitOfWork::get()->publishEvent(new GenericEventMessage(new MyApplicationEvent()),
                $this->eventBus);
        throw new StrangeCommandReceivedException("Strange command received");
    }

    /**
     * @CommandHandler
     */
    public function handleIllegalStateChange(IllegalStateChangeCommand $command)
    {
        $aggregate = $this->repository->load($command->getAggregateIdentifier());
        $aggregate->doSomethingIllegal($command->getNewIllegalValue());
    }

    /**
     * @CommandHandler
     */
    public function handleDeleteAggregate(DeleteCommand $command)
    {
        $this->repository->load($command->getAggregateIdentifier())->delete($command->isAsIllegalChange());
    }

}
