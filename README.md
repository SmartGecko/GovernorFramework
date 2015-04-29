# Governor Framework PHP CQRS library

[![Build Status](https://travis-ci.org/SmartGecko/GovernorFramework.svg?branch=master)](https://travis-ci.org/SmartGecko/GovernorFramework)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/SmartGecko/GovernorFramework/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/SmartGecko/GovernorFramework/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/SmartGecko/GovernorFramework/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/SmartGecko/GovernorFramework/?branch=master)

Governor Framework is a Command and Query Responsibility Segregation library for PHP 5.5+.

It provides components to build applications following the CQRS patterns such as:

* Command Handling
* Event Handling
* Event Sourcing
* Event Stores
* Sagas
* Unit Of Work
* Repositories 
* Serialization
* AMQP support
* Testing

The main source of inspiration for this library was the [Axon Framework](http://www.axonframework.org/ "Axon Framework") written in Java
and the Governor Framework can be viewed as a PHP port of the Axon, because it retains its basic building blocks. 

The library can be directly integrated into the Symfony 2 framework as a bundle. The core of the Symfony 2 integration was
taken from the [LiteCQRS framework](https://github.com/beberlei/litecqrs-php).

# Quick Start

## The Domain Model

To demonstrate the features of the Governor Framework we will work with a rather simple User model. 
The User class is our aggregate root. Aggregate roots in Governor must either implement 
the ```Governor\Framework\Domain\AggregateRootInterface``` interface or extend one of the built in base classes:

* ```Governor\Framework\Domain\AbstractAggregateRoot```
* ```Governor\Framework\EventSourcing\AbstractEventSourcedAggregateRoot```
* ```Governor\Framework\EventSourcing\Annotation\AbstractAnnotatedAggregateRoot```

In this example we will use the aggregate root with annotation support.
Our aggregate will also serve as a command handler in this example.

The aggregate will react to the commands

* ```CreateUserCommand```
* ```ChangeUserEmailCommand```

which will produce the corresponding events

* ```UserCreatedEvent```
* ```UserEmailChangedEvent```

Our aggregate root implementation will look the following.

```php
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

}
```

## Commands and Events

We introduce 2 operations over the aggregate - one will create a new user and the second will change the user email.
Notice the ```@Type``` annotations on the event class - Governor Framework uses the excellent JMS serializer library for 
serialization and deserialization purposes. To allow event deserialization when rebuilding the aggregate from an event stream 
the annotations must be present in order to successfully deserialize the events.

Our commands and events are implemented like this:

```php
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
```

## Event listeners

We can register event listeners to listen for events on the event bus. 
The event listeners need to implement the ```Governor\Framework\EventHandling\EventListenerInterface``` interface.

This simple listener that will listen for all events on the event bus and print their payload.

```php
class UserEventListener implements EventListenerInterface
{

    public function handle(EventMessageInterface $event)
    {
        print_r($event->getPayload());
    }

}
```

## Wrapping it all together

Now we can complete the example by setting up the necessary infrastructure in the following steps

1. We need to set up a PSR-0 compatible logger like Monolog.
2. Create a command bus and for convienience wrap it in a command gateway.
3. Initialize an event store backed by a filesystem. Note that if you don't want to use event sourcing this step can be skipped.
4. Set up a simple event bus
5. Create an event sourcing repository 
6. Subscribe the annotated aggregate root to the command bus so it can recieve commands.
7. Register an event listener that will display the content of our events.
8. Dispatch commands.

```php
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
```


# Licensing

Governor Framework is licensed under the [MIT](http://opensource.org/licenses/MIT) license.

