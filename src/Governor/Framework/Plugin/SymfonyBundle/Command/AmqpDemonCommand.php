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

namespace Governor\Framework\Plugin\SymfonyBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Governor\Framework\EventHandling\Io\EventMessageReader;
use Governor\Framework\EventHandling\Amqp\AMQPTerminal;
use Governor\Framework\Serializer\JMSSerializer;
use Governor\Framework\UnitOfWork\DefaultUnitOfWork;

/**
 * Description of AmqpDemonCommand
 *
 * @author david
 */
class AmqpDemonCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this->setName('governor:amqp-demon')
                ->addArgument("queue", InputArgument::REQUIRED,
                        "Name of the queue to process messages from")
                ->addArgument("connection", InputArgument::OPTIONAL,
                        "Name of the connection to use", "default")
                ->addArgument("cluster", InputArgument::OPTIONAL,
                        "Name of the cluster to forward messages to", "default")
                ->setDescription('Starts a demon process that forwards AMQP messages to a cluster.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $serializer = $this->getContainer()->get('governor.serializer');
        $logger = $this->getContainer()->get('logger');
        $connection = $this->getContainer()->get(sprintf("governor.amqp_terminal.connection.%s",
                        $input->getArgument("connection")));

        $eventBus = $this->getContainer()->get(sprintf("governor.event_bus.%s",
                        $input->getArgument("cluster")));

        $channel = $connection->channel();

        $callback = function($msg) use($eventBus, $input, $output, $serializer, $logger) {
            $uow = DefaultUnitOfWork::startAndGet($logger);
            $reader = new EventMessageReader($serializer);

            try {
                $message = $reader->readEventMessage($msg->body);

                $output->write(sprintf("Procecssing %s from %s\n",
                                $message->getPayloadType(),
                                $input->getArgument("queue")));

                $eventBus->getCluster()->publish(array($message));
                $uow->commit();
            } catch (\Exception $ex) {
                $uow->rollback();
                $output->write(sprintf("Encountered %s while processing",
                                $ex->getMessage()));
            }
        };

        $channel->basic_consume($input->getArgument("queue"), '', false, true,
                false, false, $callback);

        while (count($channel->callbacks)) {
            $channel->wait();
        }

        $channel->close();
        $connection->close();
    }

}
