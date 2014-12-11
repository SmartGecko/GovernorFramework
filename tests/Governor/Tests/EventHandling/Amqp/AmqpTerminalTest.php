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

namespace Governor\Tests\EventHandling\Amqp;

use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Channel\AMQPChannel;
use Governor\Framework\Domain\MetaData;
use Governor\Framework\Domain\GenericEventMessage;
use Governor\Framework\Serializer\SerializerInterface;
use Governor\Framework\UnitOfWork\CurrentUnitOfWork;
use Governor\Framework\UnitOfWork\DefaultUnitOfWork;
use Governor\Framework\UnitOfWork\NullTransactionManager;
use Governor\Framework\Serializer\SimpleSerializedObject;
use Governor\Framework\Serializer\SimpleSerializedType;
use Governor\Framework\EventHandling\Amqp\DefaultAmqpMessageConverter;
use Governor\Framework\EventHandling\Amqp\AmqpTerminal;
use Governor\Framework\EventHandling\Amqp\EventPublicationFailedException;

/**
 * Description of AmqpTerminalTest
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a> 
 */
class AmqpTerminalTest extends \PHPUnit_Framework_TestCase
{

    private $testSubject;
    private $connection;
    private $serializer;

    public function setUp()
    {
        $this->serializer = \Phake::mock(SerializerInterface::class);
        $this->testSubject = new AmqpTerminal($this->serializer, null,
                new DefaultAmqpMessageConverter($this->serializer));
        $this->connection = \Phake::mock(AMQPConnection::class);

        $this->testSubject->setExchangeName("mockExchange");
        $this->testSubject->setConnection($this->connection);
        $this->testSubject->setTransactional(true);
        $this->testSubject->setLogger($this->getMock(\Psr\Log\LoggerInterface::class));
    }

    public function tearDown()
    {
        while (CurrentUnitOfWork::isStarted()) {
            CurrentUnitOfWork::get()->rollback();
        }
    }

    public function testSendMessage_NoUnitOfWork()
    {
        $transactionalChannel = \Phake::mock(AMQPChannel::class);
        \Phake::when($this->connection)->channel()->thenReturn($transactionalChannel);

        $message = new GenericEventMessage(new Payload("Message"));

        \Phake::when($this->serializer)->serialize(\Phake::equalTo($message->getPayload()))
                ->thenReturn(new SimpleSerializedObject(json_encode($message->getPayload()),
                        new SimpleSerializedType(Payload::class)));

        \Phake::when($this->serializer)->serialize(\Phake::equalTo($message->getMetaData()))
                ->thenReturn(new SimpleSerializedObject(json_encode(array('metadata' => array())),
                        new SimpleSerializedType(MetaData::class)));

        $this->testSubject->publish(array($message));

        \Phake::verify($transactionalChannel)->basic_publish(\Phake::anyParameters());
        \Phake::verify($transactionalChannel)->tx_commit();
        \Phake::verify($transactionalChannel)->close();
    }

    public function testSendMessage_WithTransactionalUnitOfWork()
    {
        $mockTransaction = new NullTransactionManager();
        $uow = DefaultUnitOfWork::startAndGet($mockTransaction);

        $transactionalChannel = \Phake::mock(AMQPChannel::class);
        \Phake::when($transactionalChannel)->getChannelId()->thenReturn("channel");
        \Phake::when($this->connection)->channel()->thenReturn($transactionalChannel);

        $message = new GenericEventMessage(new Payload("Message"));

        \Phake::when($this->serializer)->serialize(\Phake::equalTo($message->getPayload()))
                ->thenReturn(new SimpleSerializedObject(json_encode($message->getPayload()),
                        new SimpleSerializedType(Payload::class)));

        \Phake::when($this->serializer)->serialize(\Phake::equalTo($message->getMetaData()))
                ->thenReturn(new SimpleSerializedObject(json_encode(array('metadata' => array())),
                        new SimpleSerializedType(MetaData::class)));

        $this->testSubject->publish(array($message));

        \Phake::verify($transactionalChannel)->basic_publish(\Phake::anyParameters());
        \Phake::verify($transactionalChannel, \Phake::never())->tx_commit();
        \Phake::verify($transactionalChannel, \Phake::never())->close();

        $uow->commit();

        \Phake::verify($transactionalChannel)->tx_commit();
        \Phake::verify($transactionalChannel)->close();
    }

    public function testSendMessage_WithTransactionalUnitOfWork_ChannelClosedBeforeCommit()
    {
        $mockTransaction = new NullTransactionManager();
        $uow = DefaultUnitOfWork::startAndGet($mockTransaction);

        $transactionalChannel = \Phake::mock(AMQPChannel::class);
        \Phake::when($transactionalChannel)->getChannelId()->thenReturn(null);
        \Phake::when($this->connection)->channel()->thenReturn($transactionalChannel);

        $message = new GenericEventMessage(new Payload("Message"));

        \Phake::when($this->serializer)->serialize(\Phake::equalTo($message->getPayload()))
                ->thenReturn(new SimpleSerializedObject(json_encode($message->getPayload()),
                        new SimpleSerializedType(Payload::class)));

        \Phake::when($this->serializer)->serialize(\Phake::equalTo($message->getMetaData()))
                ->thenReturn(new SimpleSerializedObject(json_encode(array('metadata' => array())),
                        new SimpleSerializedType(MetaData::class)));

        $this->testSubject->publish(array($message));

        \Phake::verify($transactionalChannel)->basic_publish(\Phake::anyParameters());
        \Phake::verify($transactionalChannel, \Phake::never())->tx_commit();
        \Phake::verify($transactionalChannel, \Phake::never())->close();

        try {
            $uow->commit();
            $this->fail("Expected exception");
        } catch (EventPublicationFailedException $ex) {
            $this->assertNotNull($ex->getMessage());
        }

        \Phake::verify($transactionalChannel, \Phake::never())->tx_commit();
    }

    public function testSendMessage_WithUnitOfWorkRollback()
    {
        $uow = DefaultUnitOfWork::startAndGet();

        $transactionalChannel = \Phake::mock(AMQPChannel::class);
        \Phake::when($this->connection)->channel()->thenReturn($transactionalChannel);

        $message = new GenericEventMessage(new Payload("Message"));

        \Phake::when($this->serializer)->serialize(\Phake::equalTo($message->getPayload()))
                ->thenReturn(new SimpleSerializedObject(json_encode($message->getPayload()),
                        new SimpleSerializedType(Payload::class)));

        \Phake::when($this->serializer)->serialize(\Phake::equalTo($message->getMetaData()))
                ->thenReturn(new SimpleSerializedObject(json_encode(array('metadata' => array())),
                        new SimpleSerializedType(MetaData::class)));

        $this->testSubject->publish(array($message));

        \Phake::verify($transactionalChannel)->basic_publish(\Phake::anyParameters());
        \Phake::verify($transactionalChannel, \Phake::never())->tx_rollback();
        \Phake::verify($transactionalChannel, \Phake::never())->tx_commit();
        \Phake::verify($transactionalChannel, \Phake::never())->close();

        $uow->rollback();

        \Phake::verify($transactionalChannel, \Phake::never())->tx_commit();
        \Phake::verify($transactionalChannel)->tx_rollback();
        \Phake::verify($transactionalChannel)->close();
    }

    public function testSendMessageWithPublisherAck_UnitOfWorkCommitted()
    {
        $this->testSubject->setTransactional(false);
        $this->testSubject->setWaitForPublisherAck(true);
        $this->testSubject->setPublisherAckTimeout(123);

        $channel = \Phake::mock(AMQPChannel::class);

        \Phake::when($channel)->wait_for_pending_acks()->thenReturn(null);
        \Phake::when($this->connection)->channel()->thenReturn($channel);

        $message = new GenericEventMessage(new Payload("Message"));

        \Phake::when($this->serializer)->serialize(\Phake::equalTo($message->getPayload()))
                ->thenReturn(new SimpleSerializedObject(json_encode($message->getPayload()),
                        new SimpleSerializedType(Payload::class)));

        \Phake::when($this->serializer)->serialize(\Phake::equalTo($message->getMetaData()))
                ->thenReturn(new SimpleSerializedObject(json_encode(array('metadata' => array())),
                        new SimpleSerializedType(MetaData::class)));

        $uow = DefaultUnitOfWork::startAndGet();

        $this->testSubject->publish(array($message));
        \Phake::verify($channel, \Phake::never())->wait_for_pending_acks();

        $uow->commit();

        \Phake::verify($channel)->confirm_select();
        \Phake::verify($channel)->basic_publish(\Phake::anyParameters());
        \Phake::verify($channel)->wait_for_pending_acks(123);
    }

    /**
     * @expectedException \LogicException
     */
    public function testCannotSetPublisherAcksAfterTransactionalSetting()
    {
        $this->testSubject->setTransactional(true);
        $this->testSubject->setWaitForPublisherAck(true);
    }

    /**
     * @expectedException \LogicException
     */
    public function testCannotSetTransactionalBehaviorAfterPublisherAcks()
    {
        $this->testSubject->setTransactional(false);

        $this->testSubject->setWaitForPublisherAck(true);
        $this->testSubject->setTransactional(true);
    }

    public function testSendMessageWithPublisherAck_NoActiveUnitOfWork()
    {
        $this->testSubject->setTransactional(false);
        $this->testSubject->setWaitForPublisherAck(true);

        $channel = \Phake::mock(AMQPChannel::class);

        \Phake::when($channel)->wait_for_pending_acks()->thenReturn(null);
        \Phake::when($this->connection)->channel()->thenReturn($channel);

        $message = new GenericEventMessage(new Payload("Message"));

        \Phake::when($this->serializer)->serialize(\Phake::equalTo($message->getPayload()))
                ->thenReturn(new SimpleSerializedObject(json_encode($message->getPayload()),
                        new SimpleSerializedType(Payload::class)));

        \Phake::when($this->serializer)->serialize(\Phake::equalTo($message->getMetaData()))
                ->thenReturn(new SimpleSerializedObject(json_encode(array('metadata' => array())),
                        new SimpleSerializedType(MetaData::class)));

        $this->testSubject->publish(array($message));

        \Phake::verify($channel)->confirm_select();
        \Phake::verify($channel)->basic_publish(\Phake::anyParameters());
        \Phake::verify($channel)->wait_for_pending_acks(\Phake::anyParameters());
    }

}

class Payload
{

    public $payload;

    function __construct($payload)
    {
        $this->payload = $payload;
    }

}
