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

namespace Governor\Framework\EventHandling\Amqp;

use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Channel\AMQPChannel;
use Governor\Framework\Domain\MetaData;
use Governor\Framework\Domain\GenericEventMessage;
use Governor\Framework\Serializer\SerializerInterface;
use Governor\Framework\UnitOfWork\CurrentUnitOfWork;
use Governor\Framework\UnitOfWork\DefaultUnitOfWork;
use Governor\Framework\Serializer\SimpleSerializedObject;
use Governor\Framework\Serializer\SimpleSerializedType;

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
        //  TransactionManager mockTransaction = new NoTransactionManager();
        $uow = DefaultUnitOfWork::startAndGet($this->getMock(\Psr\Log\LoggerInterface::class)); //mockTransaction

        $transactionalChannel = \Phake::mock(AMQPChannel::class);
        \Phake::when($transactionalChannel)->is_open()->thenReturn(true);
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
        //TransactionManager mockTransaction = new NoTransactionManager();
        $uow = DefaultUnitOfWork::startAndGet($this->getMock(\Psr\Log\LoggerInterface::class)); //mockTransaction

        $transactionalChannel = \Phake::mock(AMQPChannel::class);
        //\Phake::when($transactionalChannel)->is_open()->thenReturn(false);
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

        /*try {
            $uow->commit();
            $this->fail("Expected exception");
        } catch (EventPublicationFailedException $ex) {
            $this->assertNotNull($ex->getMessage());
        }
        
        \Phake::verify($transactionalChannel, \Phake::never())->tx_commit();*/
    }

    /*
      @Test
      public void testSendMessage_WithUnitOfWorkRollback() throws IOException {
      UnitOfWork uow = DefaultUnitOfWork.startAndGet();

      Connection connection = mock(Connection.class);
      when(connectionFactory.createConnection()).thenReturn(connection);
      Channel transactionalChannel = mock(Channel.class);
      when(connection.createChannel(true)).thenReturn(transactionalChannel);
      GenericEventMessage<String> message = new GenericEventMessage<String>("Message");
      when(serializer.serialize(message.getPayload(), byte[].class))
      .thenReturn(new SimpleSerializedObject<byte[]>("Message".getBytes(UTF_8), byte[].class, "String", "0"));
      when(serializer.serialize(message.getMetaData(), byte[].class))
      .thenReturn(new SerializedMetaData<byte[]>(new byte[0], byte[].class));
      testSubject.publish(message);

      verify(transactionalChannel).basicPublish(eq("mockExchange"), eq("java.lang"),
      eq(false), eq(false),
      any(AMQP.BasicProperties.class), isA(byte[].class));
      verify(transactionalChannel, never()).txRollback();
      verify(transactionalChannel, never()).txCommit();
      verify(transactionalChannel, never()).close();

      uow.rollback();
      verify(transactionalChannel, never()).txCommit();
      verify(transactionalChannel).txRollback();
      verify(transactionalChannel).close();
      }

      @Test
      public void testSendMessageWithPublisherAck_UnitOfWorkCommitted()
      throws InterruptedException, IOException, TimeoutException {
      testSubject.setTransactional(false);
      testSubject.setWaitForPublisherAck(true);
      testSubject.setPublisherAckTimeout(123);

      Connection connection = mock(Connection.class);
      when(connectionFactory.createConnection()).thenReturn(connection);
      Channel channel = mock(Channel.class);

      when(channel.waitForConfirms()).thenReturn(true);
      when(connection.createChannel(false)).thenReturn(channel);
      GenericEventMessage<String> message = new GenericEventMessage<String>("Message");
      when(serializer.serialize(message.getPayload(), byte[].class))
      .thenReturn(new SimpleSerializedObject<byte[]>("Message".getBytes(UTF_8), byte[].class, "String", "0"));
      when(serializer.serialize(message.getMetaData(), byte[].class))
      .thenReturn(new SerializedMetaData<byte[]>(new byte[0], byte[].class));

      UnitOfWork uow = DefaultUnitOfWork.startAndGet();

      testSubject.publish(message);
      verify(channel, never()).waitForConfirms();

      uow.commit();

      verify(channel).confirmSelect();
      verify(channel).basicPublish(eq("mockExchange"), eq("java.lang"),
      eq(false), eq(false),
      any(AMQP.BasicProperties.class), isA(byte[].class));
      verify(channel).waitForConfirmsOrDie(123);
      }

      @Test(expected = IllegalArgumentException.class)
      public void testCannotSetPublisherAcksAfterTransactionalSetting() {
      testSubject.setTransactional(true);
      testSubject.setWaitForPublisherAck(true);
      }

      @Test(expected = IllegalArgumentException.class)
      public void testCannotSetTransactionalBehaviorAfterPublisherAcks() {
      testSubject.setTransactional(false);

      testSubject.setWaitForPublisherAck(true);
      testSubject.setTransactional(true);
      }

      @Test
      public void testSendMessageWithPublisherAck_NoActiveUnitOfWork() throws InterruptedException, IOException {
      testSubject.setTransactional(false);
      testSubject.setWaitForPublisherAck(true);

      Connection connection = mock(Connection.class);
      when(connectionFactory.createConnection()).thenReturn(connection);
      Channel channel = mock(Channel.class);

      when(channel.waitForConfirms()).thenReturn(true);
      when(connection.createChannel(false)).thenReturn(channel);
      GenericEventMessage<String> message = new GenericEventMessage<String>("Message");
      when(serializer.serialize(message.getPayload(), byte[].class))
      .thenReturn(new SimpleSerializedObject<byte[]>("Message".getBytes(UTF_8), byte[].class, "String", "0"));
      when(serializer.serialize(message.getMetaData(), byte[].class))
      .thenReturn(new SerializedMetaData<byte[]>(new byte[0], byte[].class));

      testSubject.publish(message);
      verify(channel).confirmSelect();
      verify(channel).basicPublish(eq("mockExchange"), eq("java.lang"),
      eq(false), eq(false),
      any(AMQP.BasicProperties.class), isA(byte[].class));
      verify(channel).waitForConfirmsOrDie();
      }
     */
}

class Payload
{

    public $payload;

    function __construct($payload)
    {
        $this->payload = $payload;
    }

}
