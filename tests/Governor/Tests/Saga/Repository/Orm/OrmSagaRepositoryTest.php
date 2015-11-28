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

namespace Governor\Tests\Saga\Repository\Orm;

use Ramsey\Uuid\Uuid;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\Common\Annotations\AnnotationReader;
use Governor\Framework\Serializer\JMSSerializer;
use Governor\Tests\Saga\Repository\StubSaga;
use Governor\Framework\Saga\AssociationValue;
use Governor\Framework\Saga\NullResourceInjector;
use Governor\Framework\Saga\Annotation\AbstractAnnotatedSaga;
use Governor\Framework\Saga\SagaInterface;
use Governor\Framework\Domain\EventMessageInterface;
use Governor\Framework\Saga\Repository\Orm\OrmSagaRepository;
use Governor\Framework\Saga\Repository\Orm\SagaEntry;
use Governor\Framework\Saga\Repository\Orm\AssociationValueEntry;

/**
 * Description of OrmSagaRepositoryTest
 *
 * @author david
 */
class OrmSagaRepositoryTest extends \PHPUnit_Framework_TestCase
{

    private $repository;
    private static $config;
    private static $dbParams;

    /**
     * @var EntityManager
     */
    private $entityManager;
    private $serializer;

    public static function setUpBeforeClass()
    {
        // bootstrap doctrine
        self::$dbParams = array(
            'driver' => 'pdo_sqlite',
            'user' => 'root',
            'password' => '',
            'memory' => true
        );

        self::$config = Setup::createConfiguration(true);
        self::$config->setMetadataDriverImpl(new AnnotationDriver(new AnnotationReader()));
        //self::$config->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger());
    }

    public function setUp()
    {
        $this->entityManager = EntityManager::create(self::$dbParams,
                        self::$config);

        $tool = new \Doctrine\ORM\Tools\SchemaTool($this->entityManager);
        $classes = array(
            $this->entityManager->getClassMetadata(\Governor\Framework\Saga\Repository\Orm\AssociationValueEntry::class),
            $this->entityManager->getClassMetadata(\Governor\Framework\Saga\Repository\Orm\SagaEntry::class)
        );

        $tool->createSchema($classes);

        $this->serializer = new JMSSerializer();
        $this->repository = new OrmSagaRepository($this->entityManager,
                new NullResourceInjector(), $this->serializer);
    }

    public function testAddingAnInactiveSagaDoesntStoreIt()
    {
        $testSaga = new StubSaga("test1");
        $testSaga->associateWith(new AssociationValue("key", "value"));
        $testSaga->end();

        $this->repository->add($testSaga);
        $this->entityManager->flush();
        $this->entityManager->clear();
        $actual = $this->repository->find(StubSaga::class,
                new AssociationValue("key", "value"));

        $this->assertCount(0, $actual);

        $actualSaga = $this->repository->load("test1");
        $this->assertNull($actualSaga);
    }

    public function testLoadSagaOfDifferentTypesWithSameAssociationValue_SagaFound()
    {
        $testSaga = new StubSaga("test1");
        $otherTestSaga = new MyOtherTestSaga("test2");
        $testSaga->associateWith(new AssociationValue("key", "value"));

        $otherTestSaga->associateWith(new AssociationValue("key", "value"));

        $this->repository->add($testSaga);
        $this->repository->add($otherTestSaga);

        $this->entityManager->flush();
        $this->entityManager->clear();

        $actual = $this->repository->find(StubSaga::class,
                new AssociationValue("key", "value"));
        $this->assertCount(1, $actual);
        $this->assertEquals("test1", $actual[0]);
    }

    public function testLoadSagaOfDifferentTypesWithSameAssociationValue_NoSagaFound()
    {
        $testSaga = new StubSaga("test1");
        $otherTestSaga = new MyOtherTestSaga("test2");

        $this->repository->add($testSaga);
        $this->repository->add($otherTestSaga);

        $testSaga->associateWith(new AssociationValue("key", "value"));
        $otherTestSaga->associateWith(new AssociationValue("key", "value"));

        $this->entityManager->flush();
        $this->entityManager->clear();

        $actual = $this->repository->find(InexistentSaga::class,
                new AssociationValue("key", "value"));
        $this->assertCount(0, $actual);
    }

    public function testLoadSagaOfDifferentTypesWithSameAssociationValue_SagaDeleted()
    {
        $testSaga = new StubSaga("test1");
        $otherTestSaga = new MyOtherTestSaga("test2");

        $this->repository->add($testSaga);
        $this->repository->add($otherTestSaga);

        $testSaga->associateWith(new AssociationValue("key", "value"));
        $otherTestSaga->associateWith(new AssociationValue("key", "value"));
        $testSaga->end();

        $this->repository->commit($testSaga);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $actual = $this->repository->find(StubSaga::class,
                new AssociationValue("key", "value"));
        $this->assertCount(0, $actual);
    }

    public function testAddAndLoadSaga_ByIdentifier()
    {
        $identifier = Uuid::uuid1()->toString();

        $saga = new StubSaga($identifier);
        $this->repository->add($saga);

        $loaded = $this->repository->load($identifier);
        $this->assertEquals($identifier, $loaded->getSagaIdentifier());

        $this->assertNotNull($this->entityManager->find(SagaEntry::class,
                        $identifier));
    }

    public function testAddAndLoadSaga_ByAssociationValue()
    {
        $identifier = Uuid::uuid1()->toString();

        $saga = new StubSaga($identifier);
        $saga->associateWith(new AssociationValue("key", "value"));
        $this->repository->add($saga);

        $loaded = $this->repository->find(StubSaga::class,
                new AssociationValue("key", "value"));
        $this->assertCount(1, $loaded);

        $loadedSaga = $this->repository->load($loaded[0]);
        $this->assertEquals($identifier, $loadedSaga->getSagaIdentifier());
        $this->assertNotNull($this->entityManager->find(SagaEntry::class,
                        $identifier));
    }

    public function testAddAndLoadSaga_AssociateValueAfterStorage()
    {
        $identifier = Uuid::uuid1()->toString();

        $saga = new StubSaga($identifier);
        $this->repository->add($saga);

        $saga->associateWith(new AssociationValue("key", "value"));
        $this->repository->commit($saga);

        $loaded = $this->repository->find(StubSaga::class,
                new AssociationValue("key", "value"));
        $this->assertCount(1, $loaded);

        $loadedSaga = $this->repository->load($loaded[0]);
        $this->assertEquals($identifier, $loadedSaga->getSagaIdentifier());
        $this->assertNotNull($this->entityManager->find(SagaEntry::class,
                        $identifier));
    }

    /*
      public void testLoadUncachedSaga_ByAssociationValue() {
      String identifier = UUID.randomUUID().toString();
      StubSaga saga = new StubSaga(identifier);
      entityManager.persist(new SagaEntry(saga, serializer));
      entityManager.persist(new AssociationValueEntry(serializer.typeForClass(saga.getClass()).getName(),
      identifier, new AssociationValue("key", "value")));
      entityManager.flush();
      entityManager.clear();
      Set<String> loaded = repository.find(StubSaga.class, new AssociationValue("key", "value"));
      assertEquals(1, loaded.size());
      Saga loadedSaga = repository.load(loaded.iterator().next());
      assertEquals(identifier, loadedSaga.getSagaIdentifier());
      assertNotSame(loadedSaga, saga);
      assertNotNull(entityManager.find(SagaEntry.class, identifier));
      } */

    public function testLoadSaga_NotFound()
    {
        $this->assertNull($this->repository->load("123456"));
    }

    public function testLoadSaga_AssociationValueRemoved()
    {
        $identifier = Uuid::uuid1()->toString();
        $saga = new StubSaga($identifier);

        $saga->associateWith(new AssociationValue("key", "value"));
        $this->entityManager->persist(new SagaEntry($saga, $this->serializer));
        $this->entityManager->persist(new AssociationValueEntry(get_class($saga),
                $identifier, new AssociationValue("key", "value")));

        $this->entityManager->flush();
        $this->entityManager->clear();

        $loaded = $this->repository->load($identifier);

        $loaded->removeAssociationWith(new AssociationValue("key", "value"));
        $this->repository->commit($loaded);

        $found = $this->repository->find(StubSaga::class,
                new AssociationValue("key", "value"));
        $this->assertCount(0, $found);
    }

    public function testSaveSaga()
    {
        $identifier = Uuid::uuid1()->toString();
        $saga = new StubSaga($identifier);
        
        $this->entityManager->persist(new SagaEntry($saga, new JMSSerializer()));
        $this->entityManager->flush();
        
        $loaded = $this->repository->load($identifier);

        $this->repository->commit($loaded);

        $this->entityManager->clear();

        $entry = $this->entityManager->find(SagaEntry::class, $identifier);
        $actualSaga = $entry->getSaga(new JMSSerializer());
        $this->assertNotSame($loaded, $actualSaga);
    }

    public function testEndSaga()
    {
        $identifier = Uuid::uuid1()->toString();
        $saga = new StubSaga($identifier);
        $saga->associateWith(new AssociationValue("key", "value"));

        $this->repository->add($saga);
        $this->entityManager->flush();

        $this->assertCount(2,
                $this->entityManager->createQuery("SELECT ae FROM Governor\Framework\Saga\Repository\Orm\AssociationValueEntry ae WHERE ae.sagaId = :id")
                        ->setParameter(":id", $identifier)->getResult());

        $loaded = $this->repository->load($identifier);
        $loaded->end();
        $this->repository->commit($loaded);

        $this->entityManager->clear();

        $this->assertNull($this->entityManager->find(SagaEntry::class,
                        $identifier));
        $this->assertCount(0,
                $this->entityManager->createQuery("SELECT ae FROM Governor\Framework\Saga\Repository\Orm\AssociationValueEntry ae WHERE ae.sagaId = :id")
                        ->setParameter(":id", $identifier)->getResult());
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

    public function getAssociationValues()
    {
        
    }

    public function getSagaIdentifier()
    {
        
    }

    public function handle(EventMessageInterface $event)
    {
        
    }

    public function isActive()
    {
        
    }

}
