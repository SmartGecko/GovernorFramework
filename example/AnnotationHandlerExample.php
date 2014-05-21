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

namespace AnnotationHandlerExample;

$loader = require_once __DIR__ . "/../vendor/autoload.php";

use Doctrine\Common\Annotations\AnnotationRegistry;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Rhumsaa\Uuid\Uuid;
use JMS\Serializer\Annotation\Type;
use Governor\Framework\Domain\EventMessageInterface;
use Governor\Framework\EventSourcing\Annotation\AbstractAnnotatedAggregateRoot;
use Governor\Framework\CommandHandling\Handlers\AnnotatedAggregateCommandHandler;
use Governor\Framework\CommandHandling\SimpleCommandBus;
use Governor\Framework\CommandHandling\Gateway\DefaultCommandGateway;
use Governor\Framework\EventStore\Filesystem\FilesystemEventStore;
use Governor\Framework\EventStore\Filesystem\SimpleEventFileResolver;
use Governor\Framework\EventHandling\SimpleEventBus;
use Governor\Framework\EventHandling\EventListenerInterface;
use Governor\Framework\EventSourcing\EventSourcingRepository;
use Governor\Framework\EventSourcing\GenericAggregateFactory;
use Governor\Framework\Repository\NullLockManager;
use Governor\Framework\Repository\RepositoryInterface;
use Governor\Framework\UnitOfWork\UnitOfWorkInterface;
use Governor\Framework\Serializer\JMSSerializer;
use Governor\Framework\UnitOfWork\DefaultUnitOfWork;
use Governor\Framework\Annotations\AggregateIdentifier;
use Governor\Framework\Annotations\TargetAggregateIdentifier;
use Governor\Framework\Annotations\EventHandler;
use Governor\Framework\Annotations\CommandHandler;

AnnotationRegistry::registerLoader(array($loader, "loadClass"));

/**
 * Our aggregate.
 */
class User extends AbstractAnnotatedAggregateRoot
{
    /**
     * @AggregateIdentifier
     * @var string
     */
    private $identifier;
    private $email;
    
    /**
     * @CommandHandler
     * @param CreateUserCommand $command
     */
    public function __construct(CreateUserCommand $command)
    {
        $this->apply(new UserCreatedEvent($command->getIdentifier(), $command->getEmail()));
    }

    /**
     * @CommandHandler
     * @param ChangeUserEmailCommand $command
     */
    public function changeEmail(ChangeUserEmailCommand $command)
    {
        $this->apply(new UserEmailChangedEvent($this->identifier, $command->getEmail()));
    }

    /**
     * @EventHandler
     * @param UserEmailChangedEvent $event
     */
    public function onEmailChanged(UserEmailChangedEvent $event)
    {
        $this->email = $event->getEmail();
    }

    /**
     * @EventHandler
     * @param UserCreatedEvent $event
     */
    public function onUserCreated(UserCreatedEvent $event)
    {
        $this->identifier = $event->getIdentifier();
        $this->email = $event->getEmail();
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function preInitializeState()
    {
        
    }
}

abstract class AbstractUserEvent
{

    /**
     * @Type("string")
     * @var string
     */
    private $identifier;

    /**
     * @Type("string")
     * @var string
     */
    private $email;

    function __construct($identifier, $email)
    {
        $this->identifier = $identifier;
        $this->email = $email;
    }

    public function getIdentifier()
    {
        return $this->identifier;
    }

    public function getEmail()
    {
        return $this->email;
    }

}

class UserCreatedEvent extends AbstractUserEvent
{
    
}

class UserEmailChangedEvent extends AbstractUserEvent
{
    
}

abstract class AbstractUserCommand
{
    
    /**
     * @TargetAggregateIdentifier
     * @var string 
     */
    private $identifier;
    private $email;

    function __construct($identifier, $email)
    {
        $this->identifier = $identifier;
        $this->email = $email;
    }

    public function getIdentifier()
    {
        return $this->identifier;
    }

    public function getEmail()
    {
        return $this->email;
    }

}

class CreateUserCommand extends AbstractUserCommand
{
    
}

class ChangeUserEmailCommand extends AbstractUserCommand
{
    
}

class UserEventListener implements EventListenerInterface
{

    public function handle(EventMessageInterface $event)
    {
        print_r($event->getPayload());
    }

}

// set up logging 
$logger = new Logger('governor');
$logger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));

// 1. create a command bus and command gateway
$commandBus = new SimpleCommandBus();
$commandBus->setLogger($logger);
$commandGateway = new DefaultCommandGateway($commandBus);

$rootDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'CommandHandlerExample';
@mkdir($rootDirectory);
echo sprintf("Initializing FileSystemEventStore in %s\n", $rootDirectory);

// 2. initialize the event store
$eventStore = new FilesystemEventStore(new SimpleEventFileResolver($rootDirectory),
    new JMSSerializer());

// 3. create the event bus
$eventBus = new SimpleEventBus();
$eventBus->setLogger($logger);

// 4. create an event sourcing repository
$repository = new EventSourcingRepository(User::class,
    $eventBus, new NullLockManager(), $eventStore,
    new GenericAggregateFactory(User::class));

//5. create and register our commands
AnnotatedAggregateCommandHandler::subscribe(User::class, $repository, $commandBus);

//6. create and register the eventlistener
$eventListener = new UserEventListener();
$eventBus->subscribe($eventListener);

//7. send commands 
$aggregateIdentifier = Uuid::uuid1()->toString();

$commandGateway->send(new CreateUserCommand($aggregateIdentifier,
    'email@davidkalosi.com'));
$commandGateway->send(new ChangeUserEmailCommand($aggregateIdentifier,
    'newemail@davidkalosi.com'));

//8. read back aggregate from store
$uow = DefaultUnitOfWork::startAndGet($logger);
$aggregate = $repository->load($aggregateIdentifier);

echo sprintf("\n\nUser identifier:%s, email:%s, version:%s\n\n",
    $aggregate->getIdentifier(), $aggregate->getEmail(),
    $aggregate->getVersion());

$uow->rollback();
