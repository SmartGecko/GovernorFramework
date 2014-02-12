<?php

namespace Governor\Framework\Plugin\SymfonyBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\Compiler\ResolveDefinitionTemplatesPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Governor\Framework\Plugin\SymfonyBundle\DependencyInjection\GovernorFrameworkExtension;
use Governor\Framework\Plugin\SymfonyBundle\DependencyInjection\Compiler\HandlerPass;

class GovernorFrameworkExtensionTest extends \PHPUnit_Framework_TestCase
{

    private $testSubject;

    public function setUp()
    {
        $this->testSubject = $this->createTestContainer();
    }

    public function testRepositories()
    {
        $repo1 = $this->testSubject->get('dummy1.repository');
        $repo2 = $this->testSubject->get('dummy2.repository');

        $this->assertInstanceOf('Governor\Framework\Repository\RepositoryInterface', $repo1);
        $this->assertInstanceOf('Governor\Framework\Repository\RepositoryInterface', $repo2);
        $this->assertNotSame($repo1, $repo2);
        $this->assertEquals('Governor\Framework\Stubs\Dummy1Aggregate', $repo1->getClass());
        $this->assertEquals('Governor\Framework\Stubs\Dummy2Aggregate', $repo2->getClass());
    }

    public function createTestContainer()
    {
        $config = array('governor' => array('repositories' => array('dummy1' => array(
                        'aggregate_root' => 'Governor\Framework\Stubs\Dummy1Aggregate',
                        'type' => 'doctrine'), 'dummy2' => array('aggregate_root' => 'Governor\Framework\Stubs\Dummy2Aggregate',
                        'type' => 'doctrine'))
                , 'aggregate_command_handlers' => array('dummy1' => array('aggregate_root' => 'Governor\Framework\Stubs\Dummy1Aggregate',
                        'repository' => 'dummy1.repository'),
                    'dummy2' => array('aggregate_root' => 'Governor\Framework\Stubs\Dummy2Aggregate',
                        'repository' => 'dummy2.repository'))));

        $container = new ContainerBuilder(new ParameterBag(array(
            'kernel.debug' => false,
            'kernel.bundles' => array(),
            'kernel.cache_dir' => sys_get_temp_dir(),
            'kernel.environment' => 'test',
            'kernel.root_dir' => __DIR__ . '/../../../../' // src dir
        )));
        $loader = new GovernorFrameworkExtension();
        $container->registerExtension($loader);
        $container->set('doctrine.orm.default_entity_manager', $this->getMock('Doctrine\ORM\EntityManager', array(
                    'find', 'flush', 'persist', 'remove'), array(), '', false));

        $loader->load($config, $container);

        //  $container->addCompilerPass(new HandlerPass(), PassConfig::TYPE_AFTER_REMOVING);
        $container->compile();

        return $container;
    }

}
