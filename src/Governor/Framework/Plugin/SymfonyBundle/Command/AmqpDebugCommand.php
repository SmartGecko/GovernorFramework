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

/**
 * Description of AmqpDebugCommand
 *
 * @author david
 */
class AmqpDebugCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this
                ->setName('governor:amqp-debug')
                ->addArgument("connection", InputArgument::OPTIONAL,
                        "Name of the connection to use", "default")
                ->setDescription('Displays events routed to the AMQP terminal.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $formatter = $this->getHelperSet()->get('formatter');
        $connection = $this->getContainer()->get(sprintf("governor.amqp_terminal.connection.%s",
                        $input->getArgument("connection")));

        $channel = $connection->channel();

        $output->writeln($formatter->formatSection('Connecting',
                        'Using connection ' . $input->getArgument('connection')));

        list($queueName,, ) = $channel->queue_declare("", false, false, true,
                false);
        $channel->queue_bind($queueName, AMQPTerminal::DEFAULT_EXCHANGE_NAME,
                '#');

        $output->writeln($formatter->formatSection('*',
                        'Waiting for events. To exit press CTRL+C'));

        $callback = function($msg) use($output, $formatter) {
            $reader = new EventMessageReader(new JMSSerializer());
            $message = $reader->readEventMessage($msg->body);

            $output->writeln($formatter->formatSection('*',
                            sprintf("Recieved event with routing key %s",
                                    $msg->delivery_info['routing_key'])));
            $output->writeln(array(' [x] ' . print_r($message, true)));
        };

        $channel->basic_consume($queueName, '', false, true, false, false,
                $callback);

        while (count($channel->callbacks)) {
            $channel->wait();
        }

        $channel->close();
        $connection->close();
    }

}
