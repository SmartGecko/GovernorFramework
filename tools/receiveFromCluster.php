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

namespace Test;

use Governor\Framework\UnitOfWork\UnitOfWorkInterface;
use Governor\Framework\CommandHandling\CommandMessageInterface;
use Governor\Framework\CommandHandling\CommandHandlerInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

$loader = require __DIR__ . "/../vendor/autoload.php";
$loader->add('Governor\\Tests', __DIR__);

\Doctrine\Common\Annotations\AnnotationRegistry::registerLoader(array($loader, 'loadClass'));

$logger = new Logger('governor');
$logger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));

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


$amqp = new \PhpAmqpLib\Connection\AMQPStreamConnection('localhost', 5672, 'guest', 'guest');

$cluster = new \Governor\Framework\Cluster\ZookeeperCluster('localhost:2181');

$cluster->setLogger($logger);
$cluster->connect('test');

$serializer = new \Governor\Framework\Serializer\JMSSerializer();
$uowFactory = new \Governor\Framework\UnitOfWork\DefaultUnitOfWorkFactory();

$commandBus = new \Governor\Framework\CommandHandling\SimpleCommandBus($uowFactory);
$commandBus->subscribe('Test\\Command', new TestHandler());

$commandWorker = new \Governor\Framework\CommandHandling\Distributed\Amqp\AmqpCommandHandlerWorker('business', $amqp, $serializer, $commandBus);
$commandWorker->setLogger($logger);
$cluster->registerNode($commandWorker);

$commandWorker->start();