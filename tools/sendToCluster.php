<?php
/**
 * This file is part of the SmartGecko(c) business platform.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Test;

use Governor\Framework\UnitOfWork\UnitOfWorkInterface;
use Governor\Framework\CommandHandling\CommandMessageInterface;
use Governor\Framework\CommandHandling\CommandHandlerInterface;
use Governor\Framework\CommandHandling\Callbacks\ResultCallback;
use Governor\Framework\CommandHandling\GenericCommandMessage;
use Governor\Framework\Domain\MetaData;
use Governor\Framework\CommandHandling\Distributed\MetaDataRoutingStrategy;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

$loader = require __DIR__ . "/../vendor/autoload.php";
$loader->add('Governor\\Tests', __DIR__);

\Doctrine\Common\Annotations\AnnotationRegistry::registerLoader(array($loader, 'loadClass'));

class TestHandler implements CommandHandlerInterface
{
    /**
     * @inheritDoc
     */
    public function handle(
        CommandMessageInterface $commandMessage,
        UnitOfWorkInterface $unitOfWork
    ) {
        var_dump($commandMessage);
    }

}

class Command
{
    public $aggregateIdentifier;

    public $msg;

    /**
     * Command constructor.
     * @param $aggregateIdentifier
     * @param $msg
     */
    public function __construct($aggregateIdentifier, $msg)
    {
        $this->aggregateIdentifier = $aggregateIdentifier;
        $this->msg = $msg;
    }


}



$logger = new Logger('governor');
$logger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));

$amqp = new \PhpAmqpLib\Connection\AMQPStreamConnection('localhost', 5672, 'guest', 'guest');

$cluster = new \Governor\Framework\Cluster\ZookeeperCluster('localhost:2181');
$cluster->setLogger($logger);
$cluster->connect('test');

$serializer = new \Governor\Framework\Serializer\JMSSerializer();
$uowFactory = new \Governor\Framework\UnitOfWork\DefaultUnitOfWorkFactory();

$local = new \Governor\Framework\CommandHandling\SimpleCommandBus($uowFactory);
$connector = new \Governor\Framework\CommandHandling\Distributed\Amqp\AmqpCommandBusConnector($amqp, $cluster, $local, $serializer);

$distributed = new \Governor\Framework\CommandHandling\Distributed\DistributedCommandBus($connector, new MetaDataRoutingStrategy('id'));;
$distributed->subscribe('Test\\Command', new TestHandler());

$msg = new GenericCommandMessage(new Command('a','b'), new MetaData(['id' => 'a']));

$callback = new ResultCallback();
$distributed->dispatch($msg, $callback);

var_dump($callback->getResult());




