<?php

/**
 * This example shows the implementation for a "change email"
 * command on a User entity. The command accepts a user id
 * and a new email address. The change is delegated to the
 * user object where the "DomainObjectChanged" event is raised.
 *
 * A listener picks up this event and displays the changed e-mail.
 */

namespace CommandHandlerExample;

$loader = require_once __DIR__ . "/../vendor/autoload.php";

use Doctrine\Common\Annotations\AnnotationRegistry;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Rhumsaa\Uuid\Uuid;
use JMS\Serializer\Annotation\Type;
use Governor\Framework\Domain\DomainEventMessageInterface;
use Governor\Framework\Domain\EventMessageInterface;
use Governor\Framework\EventSourcing\AbstractEventSourcedAggregateRoot;
use Governor\Framework\CommandHandling\CommandMessageInterface;
use Governor\Framework\CommandHandling\CommandHandlerInterface;
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

AnnotationRegistry::registerLoader(array($loader, "loadClass"));

// start default UOW
//DefaultUnitOfWork::startAndGet();

/**
 * Our aggregate.
 */
class User extends AbstractEventSourcedAggregateRoot
{

    private $identifier;
    private $email;

    public function __construct($identifier, $email)
    {
        $this->apply(new UserCreatedEvent($identifier, $email));
    }

    public function changeEmail($email)
    {
        $this->apply(new UserEmailChangedEvent($this->identifier, $email));
    }

    public function onEmailChanged(UserEmailChangedEvent $event)
    {
        $this->email = $event->getEmail();
    }

    public function onUserCreated(UserCreatedEvent $event)
    {
        $this->identifier = $event->getIdentifier();
        $this->email = $event->getEmail();
    }

    protected function getChildEntities()
    {
        return null;
    }

    protected function handle(DomainEventMessageInterface $event)
    {
        $payload = $event->getPayload();

        switch ($event->getPayloadType()) {
            case 'CommandHandlerExample\UserCreatedEvent':
                $this->onUserCreated($payload);
                break;
            case 'CommandHandlerExample\UserEmailChangedEvent':
                $this->onEmailChanged($payload);
                break;
        }
    }

    public function getIdentifier()
    {
        return $this->identifier;
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

class UserCommandHandler implements CommandHandlerInterface
{

    private $repository;

    public function __construct(RepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function handle(CommandMessageInterface $commandMessage,
        UnitOfWorkInterface $unitOfWork)
    {
        $command = $commandMessage->getPayload();

        switch ($commandMessage->getPayloadType()) {
            case 'CommandHandlerExample\CreateUserCommand':
                $aggregate = new User($command->getIdentifier(),
                    $command->getEmail());
                $this->repository->add($aggregate);
                break;
            case 'CommandHandlerExample\ChangeUserEmailCommand':
                $aggregate = $this->repository->load($command->getIdentifier());
                $aggregate->changeEmail($command->getEmail());
                break;
        }
    }

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
$repository = new EventSourcingRepository('CommandHandlerExample\User',
    $eventBus, new NullLockManager(), $eventStore,
    new GenericAggregateFactory('CommandHandlerExample\User'));

//5. create and register our commands
$commandHandler = new UserCommandHandler($repository);
$commandBus->subscribe('CommandHandlerExample\CreateUserCommand',
    $commandHandler);
$commandBus->subscribe('CommandHandlerExample\ChangeUserEmailCommand',
    $commandHandler);

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
echo sprintf("User identifier:%s, email:%s, version:%s",
    $aggregate->getIdentifier(), $aggregate->getEmail(),
    $aggregate->getVersion());

$uow->commit();