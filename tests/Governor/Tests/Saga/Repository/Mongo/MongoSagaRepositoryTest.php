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

namespace Governor\Tests\Saga\Repository\Mongo;

use Ramsey\Uuid\Uuid;
use JMS\Serializer\Annotation as Serializer;
use Governor\Framework\Domain\EventMessageInterface;
use Governor\Framework\Saga\Repository\Mongo\MongoSagaRepository;
use Governor\Framework\Saga\SagaInterface;
use Governor\Framework\Saga\AssociationValue;
use Psr\Log\LoggerInterface;
use Governor\Framework\Saga\Annotation\AbstractAnnotatedSaga;
use Governor\Framework\Serializer\JMSSerializer;
use Governor\Framework\Saga\NullResourceInjector;
use Governor\Framework\Saga\Repository\Mongo\SagaEntry;
use Governor\Framework\Saga\Repository\Mongo\DefaultMongoTemplate;

class MongoSagaRepositoryTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var MongoSagaRepository
     */
    private $repository;

    /**
     * @var DefaultMongoTemplate
     */
    private $mongoTemplate;

    public function setUp()
    {

        try {
            $this->mongoTemplate = new DefaultMongoTemplate('mongodb://localhost:27017', 'governortest');

            $this->repository = new MongoSagaRepository(
                $this->mongoTemplate,
                new NullResourceInjector(),
                new JMSSerializer()
            );

            $this->mongoTemplate->sagaCollection()->remove([]);
        } catch (\Exception $ex) {
            $this->logger->error("No Mongo instance found. Ignoring test.");
        }

    }


    public function testLoadSagaOfDifferentTypesWithSameAssociationValue_SagaFound()
    {
        $testSaga = new MyTestSaga("test1");
        $otherTestSaga = new MyOtherTestSaga("test2");

        $testSaga->associateWith(new AssociationValue("key", "value"));
        $otherTestSaga->associateWith(new AssociationValue("key", "value"));

        $this->repository->add($testSaga);
        $this->repository->add($otherTestSaga);

        $actual = $this->repository->find(MyTestSaga::class, new AssociationValue("key", "value"));
        $this->assertCount(1, $actual);
        $this->assertInstanceOf(MyTestSaga::class, $this->repository->load($actual[0]));
        //assertEquals(MyTestSaga.class, repository.load(actual.iterator().next()).getClass());

        $actual2 = $this->repository->find(MyOtherTestSaga::class, new AssociationValue("key", "value"));
        $this->assertCount(1, $actual2);
        $this->assertInstanceOf(MyOtherTestSaga::class, $this->repository->load($actual2[0]));
        //assertEquals(MyOtherTestSaga.class, repository.load(actual2.iterator().next()).getClass());

        $sagaQuery = SagaEntry::queryByIdentifier("test1");
        $sagaCursor = $this->mongoTemplate->sagaCollection()->find($sagaQuery);
        $this->assertCount(1, $sagaCursor);
    }


    public function testLoadSagaOfDifferentTypesWithSameAssociationValue_NoSagaFound()
    {
        $testSaga = new MyTestSaga("test1");
        $testSaga->associateWith(new AssociationValue("key", "value"));
        $otherTestSaga = new MyOtherTestSaga("test2");
        $otherTestSaga->associateWith(new AssociationValue("key", "value"));

        $this->repository->add($testSaga);
        $this->repository->add($otherTestSaga);

        $actual = $this->repository->find(InexistentSaga::class, new AssociationValue("key", "value"));
        $this->assertEmpty($actual, "Didn't expect any sagas");
    }


    public function testLoadSagaOfDifferentTypesWithSameAssociationValue_SagaDeleted()
    {
        $testSaga = new MyTestSaga("test1");
        $otherTestSaga = new MyOtherTestSaga("test2");

        $this->repository->add($testSaga);
        $testSaga->associateWith(new AssociationValue("key", "value"));
        $otherTestSaga->associateWith(new AssociationValue("key", "value"));
        $testSaga->end(); // make the saga inactive

        $this->repository->add($otherTestSaga);
        $this->repository->commit($testSaga); //remove the saga because it is inactive

        $actual = $this->repository->find(MyTestSaga::class, new AssociationValue("key", "value"));
        $this->assertEmpty($actual, "Didn't expect any sagas");

        $sagaQuery = SagaEntry::queryByIdentifier("test1");
        $sagaCursor = $this->mongoTemplate->sagaCollection()->find($sagaQuery);
        $this->assertCount(0, $sagaCursor);
    }


    public function testAddAndLoadSaga_ByIdentifier()
    {
        $identifier = Uuid::uuid1()->toString();

        $saga = new MyTestSaga($identifier);
        $this->repository->add($saga);
        $loaded = $this->repository->load($identifier);
        $this->assertEquals($identifier, $loaded->getSagaIdentifier());
        $this->assertNotEmpty($this->mongoTemplate->sagaCollection()->find(SagaEntry::queryByIdentifier($identifier)));
    }


    public function testAddAndLoadSaga_ByAssociationValue()
    {
        $identifier = Uuid::uuid1()->toString();
        $saga = new MyTestSaga($identifier);

        $saga->associateWith(new AssociationValue("key", "value"));
        $this->repository->add($saga);
        $loaded = $this->repository->find(MyTestSaga::class, new AssociationValue("key", "value"));
        $this->assertCount(1, $loaded);

        $loadedSaga = $this->repository->load($loaded[0]);
        $this->assertEquals($identifier, $loadedSaga->getSagaIdentifier());
        $this->assertNotEmpty($this->mongoTemplate->sagaCollection()->find(SagaEntry::queryByIdentifier($identifier)));
    }


    public function testAddAndLoadSaga_MultipleHitsByAssociationValue()
    {
        $identifier1 = Uuid::uuid1()->toString();
        $identifier2 = Uuid::uuid1()->toString();

        $saga1 = new MyTestSaga($identifier1);
        $saga2 = new MyOtherTestSaga($identifier2);

        $saga1->associateWith(new AssociationValue("key", "value"));
        $saga2->associateWith(new AssociationValue("key", "value"));

        $this->repository->add($saga1);
        $this->repository->add($saga2);

        // load saga1
        $loaded1 = $this->repository->find(MyTestSaga::class, new AssociationValue("key", "value"));
        $this->assertCount(1, $loaded1);
        $loadedSaga1 = $this->repository->load($loaded1[0]);
        $this->assertEquals($identifier1, $loadedSaga1->getSagaIdentifier());
        $this->assertNotEmpty($this->mongoTemplate->sagaCollection()->find(SagaEntry::queryByIdentifier($identifier1)));

        // load saga2
        $loaded2 = $this->repository->find(MyOtherTestSaga::class, new AssociationValue("key", "value"));
        $this->assertCount(1, $loaded2);
        $loadedSaga2 = $this->repository->load($loaded2[0]);
        $this->assertEquals($identifier2, $loadedSaga2->getSagaIdentifier());
        $this->assertNotEmpty($this->mongoTemplate->sagaCollection()->find(SagaEntry::queryByIdentifier($identifier2)));
    }


    public function testAddAndLoadSaga_AssociateValueAfterStorage()
    {
        $identifier = Uuid::uuid1()->toString();
        $saga = new MyTestSaga($identifier);

        $this->repository->add($saga);
        $saga->associateWith(new AssociationValue("key", "value"));
        $this->repository->commit($saga);

        $loaded = $this->repository->find(MyTestSaga::class, new AssociationValue("key", "value"));
        $this->assertCount(1, $loaded);
        $loadedSaga = $this->repository->load($loaded[0]);

        $this->assertEquals($identifier, $loadedSaga->getSagaIdentifier());
        $this->assertNotEmpty($this->mongoTemplate->sagaCollection()->find(SagaEntry::queryByIdentifier($identifier)));
    }


    public function testLoadUncachedSaga_ByIdentifier()
    {
        $identifier = Uuid::uuid1()->toString();
        $saga = new MyTestSaga($identifier);

        $entry = new SagaEntry($saga, new JMSSerializer());

        $this->mongoTemplate->sagaCollection()->save($entry->asDBObject());
        $loaded = $this->repository->load($identifier);
        $this->assertNotSame($saga, $loaded);
        $this->assertEquals($identifier, $loaded->getSagaIdentifier());
    }


    public function testLoadSaga_NotFound()
    {
        $this->assertNull($this->repository->load("123456"));
    }


    public function testLoadSaga_AssociationValueRemoved()
    {
        $identifier = Uuid::uuid1()->toString();
        $saga = new MyTestSaga($identifier);

        $saga->associateWith(new AssociationValue("key", "value"));
        $this->mongoTemplate->sagaCollection()->save((new SagaEntry($saga, new JMSSerializer()))->asDBObject());

        $loaded = $this->repository->load($identifier);
        $loaded->removeAssociationWith(new AssociationValue("key", "value"));

        $this->repository->commit($loaded);
        $found = $this->repository->find(MyTestSaga::class, new AssociationValue("key", "value"));
        $this->assertCount(0, $found);

    }


    public function testSaveSaga()
    {
        /*
       $identifier = Uuid::uuid1()->toString();
       $saga = new MyTestSaga($identifier);

       $this->mongoTemplate->sagaCollection()->save((new SagaEntry($saga, new JMSSerializer()))->asDBObject());
       $loaded = $this->repository->load($identifier);
       $loaded->counter = 1;
       $this->repository->commit($loaded);

      $entry = new SagaEntry($tmongoTemplate.sagaCollection().findOne(SagaEntry
   .queryByIdentifier(identifier)));
   MyTestSaga actualSaga = (MyTestSaga) entry.getSaga(new JavaSerializer());
   assertNotSame(loaded, actualSaga);
   assertEquals(1, actualSaga.counter);*/
    }


    public function testEndSaga()
    {
        $identifier = Uuid::uuid1()->toString();
        $saga = new MyTestSaga($identifier);

        $this->mongoTemplate->sagaCollection()->save((new SagaEntry($saga, new JMSSerializer()))->asDBObject());
        $loaded = $this->repository->load($identifier);
        $loaded->end();
        $this->repository->commit($loaded);

        $this->assertNull(
            $this->mongoTemplate->sagaCollection()->findOne(SagaEntry::queryByIdentifier($identifier))
        );
    }


}

class MyTestSaga extends AbstractAnnotatedSaga
{

    /**
     * @Serializer\Type("integer")
     * @var int
     */
    public $counter = 0;

    public function __construct($identifier)
    {
        parent::__construct($identifier);
    }

    public function end()
    {
        parent::end();
    }


}

class MyOtherTestSaga extends AbstractAnnotatedSaga
{


    public function __construct($identifier)
    {
        parent::__construct($identifier);
    }
}

class InexistentSaga implements SagaInterface
{
    public function getSagaIdentifier()
    {
        throw new \RuntimeException();
    }


    public function getAssociationValues()
    {
        throw new \RuntimeException();
    }


    public function handle(EventMessageInterface $event)
    {
        throw new \RuntimeException();
    }


    public function isActive()
    {
        throw new \RuntimeException();
    }

}